portalApp.controller('chatCtrl', function ($scope, $state, $rootScope, $uibModal, $window, $document, Auth, $cookies, Socket, Security) {
  var auth = new Auth();
  $scope.$window = $window;
  $cookies.put('chatWindow','true');
  $window.onbeforeunload = function(){
    if ($scope.chatMessagesWindow)
      $scope.chatMessagesWindow.close();
    $cookies.put('chatWindow', 'false');
  }
  $scope.sock = Socket;
  $scope.statusDesc = ['Не в сети', 'В сети', 'Отошел', 'Выходной', 'В отпуске', 'В командировке', 'На больничном', 'Не в штате'];
  $window.statusDesc = $scope.statusDesc;
  $scope.new_message = document.getElementById('new_message');
  window.addEventListener("click",function twiddle(){ //for mobile
    $scope.new_message.play();
    $scope.new_message.pause();
    window.removeEventListener("click",twiddle);
  });

  Socket.get(function(socket) {
    $window.messageUsers = {};
    $window.chatMessages = {};
    $window.toUserId = "";
    $window.confs = {};
    $window.users = {};
    $window.usersArr = [];
    $window.guests = {};
    $window.chat = {text:""};
    $window.chatUnreadMessages = 0;
    $window.focusChatWindow = "hidden";

    $scope.$watch('$window.chatUnreadMessages', function(v){
      window.opener.setUnreadMessages(v);
    });
    socket.on('currentUser', function(user) {
      window.document.title=user.portalData.LastName + " " + user.portalData.name + " - коммуникатор";
      $window.user = user;
      $window.user.chatStatus = {id:1, text:''};
    })

    socket.on('logout', function() {
      auth.logout();
    })

    socket.on('disconnect', function() {
      $window.messageUsers = {};
      $window.chatMessages = {};
      $window.toUserId = "";
      $window.confs = {};
      $window.users = {};
      $window.guests = {};
      $window.chatUnreadMessages = 0;
    })

    socket.on("user_list", function(users) {
      $window.users = {};
      $window.usersArr = [];
      angular.forEach(users, function(usr) {
        var sec = new Security(usr.data.portalData);
        if (usr.data.username != $window.user.username && sec.hasPrivilege('read','all') ){
          $window.users[usr.data.username] = {phone: usr.data.portalData.ContactWithMobileFhone?usr.data.portalData.ContactWithMobileFhone[0]:"", status: usr.status, name: usr.data.portalData.LastName + " " + usr.data.portalData.name, id: usr.data.username, unread_messages: 0, sockets: usr.sockets, section: usr.data.portalData.section};
          $window.usersArr.push($window.users[usr.data.username]);
        }
      })
      if(!$scope.$$phase) $scope.$apply();
    });
    
    socket.on("guest_list", function(guests) {
      $window.guests = guests;
      if(!$scope.$$phase) $scope.$apply();
    })

    socket.on("message", function(data) {
      console.log("income message:"+ JSON.stringify(data));
      if (data.from != $window.user.username){
        $scope.new_message.play();
        if( (typeof Notification != "undefined" && Notification.permission.toLowerCase() === "granted") && 
            (($window.confs[data.to] && data.to != $window.toUserId) ||
             (!$window.confs[data.to] && data.from != $window.toUserId) ||
              !$scope.chatMessagesWindow ||
              $scope.chatMessagesWindow.closed ||
              $window.focusChatWindow === "hidden")){
          nameConf = $window.confs[data.to]?'('+$window.confs[data.to].name+')':'';
          var notify = new Notification($window.users[data.from]?$window.users[data.from].name+nameConf:data.from, {
              tag : data.from,
              body : data.message,
              icon : "/public/img_site/thumb_"+data.from+".jpeg"
          });
          notify.onclick = function(e){
            e.preventDefault();
            if($window.users[data.from]){
              $scope.selectUser(data.from, $window.users[data.from].name, 'empl');
            }else if($window.guests[data.from]){
              $scope.selectUser(data.from, $window.guests[data.from].name, 'guest');
            }else if($window.confs[data.to]){
              $scope.selectUser(data.to, $window.confs[data.to].name, 'Conf');
            }
          }
        }
      }
      if (data.from != $window.user.username && !$window.confs[data.to]){
        if($window.toUserId){
          $window.messageUsers[data.from] = {name: ($window.users[data.from]||$window.guests[data.from]).name, type: $window.users[data.from]?'empl':'guest'};
          $scope.openChatWindow();
        }else{
          us = $window.users[data.from]||$window.guests[data.from];
          $scope.selectUser(data.from, us.name, $window.users[data.from]?'empl':'guest');
        }

        if (!$window.chatMessages[data.from]) {
          $window.chatMessages[data.from] = [];
          $window.history(false, data.from);
        }
        $window.chatMessages[data.from].push({user: $window.users[data.from], message: data.message, date: data.date});
        if (data.from != $window.toUserId || (!$scope.chatMessagesWindow || $scope.chatMessagesWindow.closed)){
          if ($window.users[data.from]){
            $window.users[data.from].unread_messages += 1;
            $window.messageUsers[data.from] = {name: $window.users[data.from].name, type: 'empl'};
          }else if($window.confs[data.from]){
            $window.confs[data.from].unread_messages += 1;
            $window.messageUsers[data.from] = {name: $window.confs[data.from].name, type: 'conf'};
          }else{
            $window.guests[data.from].unread_messages += 1;
            $window.messageUsers[data.from] = {name: $window.guests[data.from].name, type: 'guest'};
          }
          $window.chatUnreadMessages += 1;
          if(!$scope.$$phase) $scope.$apply();
        }
        if (data.from == $window.toUserId){
          if(!$scope.$$phase) $scope.$apply();
          // angular.element("#chatMessagesScrollable").scrollTop(angular.element("#chatMessagesScrollable")[0].scrollHeight);
        }
      } else {
        if($window.toUserId){
          $window.messageUsers[data.to] = {name: ($window.users[data.to]||$window.guests[data.to]||$window.confs[data.to]).name, type: $window.users[data.to]?'empl':$window.confs[data.to]?'conf':'guest'};
          $scope.openChatWindow();
        }else{
          us = $window.users[data.to]||$window.guests[data.to];
          $scope.selectUser(data.to, us.name, $window.users[data.to]?'empl':'guest');
        }
        if (!$window.chatMessages[data.to]) {
          $window.chatMessages[data.to] = [];
          $window.history(false, data.to);
        }
        $window.chatMessages[data.to].push({user: {id: data.from, name: data.from!=$window.user.username?$window.users[data.from].name:$window.user.portalData.LastName + " " + $window.user.portalData.name}, message: data.message, date: data.date});
        if (data.to != $window.toUserId || (!$scope.chatMessagesWindow || $scope.chatMessagesWindow.closed)){
          if ($window.users[data.to]){
            $window.users[data.to].unread_messages += 1;
            $window.messageUsers[data.to] = {name: $window.users[data.to].name, type: 'empl'};
          }else if ($window.confs[data.to]){
            $window.confs[data.to].unread_messages += 1;
            $window.messageUsers[data.to] = {name: $window.confs[data.to].name, type: 'conf'};
          }else{
            $window.guests[data.to].unread_messages += 1;
            $window.messageUsers[data.to] = {name: $window.guests[data.to].name, type: 'guest'};
          }
          $window.chatUnreadMessages += 1;
          if(!$scope.$$phase) $scope.$apply();
        }
        if (data.to == $window.toUserId){
          if(!$scope.$$phase) $scope.$apply();
          // angular.element("#chatMessagesScrollable").scrollTop(angular.element("#chatMessagesScrollable")[0].scrollHeight);
        }
      }
      $scope.refresChatMessages();
    })

    socket.on('systemMessage', function(data) {
      $window.chatMessages[data.to].push({user: {name:'Система'}, message: data.message, date: data.date});
      if(!$scope.$$phase) $scope.$apply();
      $scope.refresChatMessages();
    })

    socket.on('createRoom', function(rooms) {
      angular.forEach(rooms, function(room) {
        console.log(room);
        $window.confs[room.id] = {name: room.name, id: room.id, type: "Conf", members: room.members, unread_messages: 0};
      })
      if(!$scope.$$phase) $scope.$apply();
    })

    socket.on('renameConf', function(data) {
      $window.confs[data.confId].name = data.confName;
      if(!$scope.$$phase) $scope.$apply();
    })

    socket.on('updateRoom', function(room) {
      $window.confs[room.id] = {name: room.name, id: room.id, type: "Conf", members: room.members, unread_messages: 0};
      if ($window.messageUsers[room.id]){
        $window.messageUsers[room.id].name = room.name;
      }
      if(!$scope.$$phase) $scope.$apply();
    })

    socket.on('guestMessage', function(data) {
      console.log(data);
      $scope.new_message.play();
      if (!$window.chatMessages[data.from.id]) {
        $window.chatMessages[data.from.id] = [];
      }
      $window.chatMessages[data.from.id].push({user: $window.guests[data.from.id], message: data.message, date: data.date});
      if ($window.toUserId !== data.from.id) {
        if (!$window.guests[data.from.id].unread_messages) $window.guests[data.from.id].unread_messages = 0;
        $window.guests[data.from.id].unread_messages += 1;
        $window.chatUnreadMessages += 1;
      }
      if(!$scope.$$phase) $scope.$apply();
    })

    $scope.selectUser = function(id, name, type) {
      $window.selectUser(id, name, type);
    }

    $window.selectUser = function(id, name, type) {
      $window.toType = type;
      $window.toUserId = id;
      $window.chatMessagesVisible = "";
      if (!$window.messageUsers[id]) {
        $window.messageUsers[id] = {name: name, type: type};
        if ($window.toType != 'Guest' && !$window.chatMessages[$window.toUserId]){
          $window.history(id);
        }
      }
      if ($window.toType=="empl"){
        $window.chatUnreadMessages -= $window.users[id].unread_messages;
        $window.users[id].unread_messages = 0;
      }else if($window.toType == 'Conf'){
        $window.chatUnreadMessages -= $window.confs[id].unread_messages;
        $window.confs[id].unread_messages = 0;
      }else{
        if (!$window.guests[id]) {
          delete $window.guests[id];
        }else{
          $window.chatUnreadMessages -= $window.guests[id].unread_messages;
          $window.guests[id].unread_messages = 0;
        }
      }
      $scope.openChatWindow(true);
      $scope.refresChatMessages();
      if(!$scope.$$phase) $scope.$apply();
    }

    $scope.selectUnread = function() {
      angular.forEach($window.users, function(u) {
        if (u.unread_messages > 0) {
          $scope.selectUser(u.id, u.name, 'empl');
          $scope.openChatWindow(true);
          return true;
        }
      });
      angular.forEach($window.confs, function(u) {
        if (u.unread_messages > 0) {
          $scope.selectUser(u.id, u.name, "Conf");
          $scope.openChatWindow(true);
          return true;
        }
      })
      angular.forEach($window.guests, function(u) {
        if (u.unread_messages > 0) {
          $scope.selectUser(u.id, u.name, "guest");
          $scope.openChatWindow(true);
          return true;
        }
      })
      $window.chatUnreadMessages = 0;
    }

    $window.chatSend = function(text, callback) {
      console.log("chatSend", text);
      if (text){
        console.log("Отправить чат");
        socket.emit("chatMessage", {from: $window.user.username, to: $window.toUserId, message: text, sms: $window.sms}, function() {
          if (!$window.chatMessages[$window.toUserId]){
            $window.chatMessages[$window.toUserId] = [];
          }
          //$window.chatMessages[$window.toUserId].push({user: {id: $window.user.username, name: $window.user.portalData.LastName + " " + $window.user.portalData.name}, message: $window.chat.text, date: Date.now()});
          $scope.refresChatMessages();
          if (callback) callback();
          //angular.element("#chatMessagesScrollable").scrollTop(angular.element("#chatMessagesScrollable")[0].scrollHeight);
        })
      }
    }

    $window.chatCreateRoom = function(name, callback) {
      socket.emit("createRoom", {name: name}, function(roomId) {
        callback(roomId);
      })
    }

    $window.confSend = function(text, callback) {
      if (text){
        console.log("Отправить конф");
        socket.emit("confMessage", {from: $window.user.username, to: $window.toUserId, message: text}, function() {
          $scope.refresChatMessages();
          if (callback) callback();
        })
      }
    }

    $window.guestSend = function(text, callback) {
      if (text){
        $scope.new_message.play();
        console.log("Отправить гостю");
        socket.emit("guestMessage", {from: {id: $window.user.username, name: $window.user.portalData.LastName + " " + $window.user.portalData.name}, to: {id: $window.toUserId, name: $window.guests[$window.toUserId].name}, message: text}, function() {
          if (!$window.chatMessages[$window.toUserId]) $window.chatMessages[$window.toUserId] = [];
          $window.chatMessages[$window.toUserId].push({user: {id: $window.user.username, name: $window.user.portalData.LastName + " " + $window.user.portalData.name}, message: text, date: Date.now()})
          if (callback) callback();
        })
      }
    }

    $scope.chatRenameConf = function(confId, confName) {
      console.log("Переименовать конференцию");
      $scope.editName = false;
      socket.emit("renameConf", {confId: confId, confName:confName});
    }

    $window.addUserToConf = function(usr, roomId) {
      if (!roomId) roomId = $window.toUserId;
      socket.emit('joinRoom', {userId: usr, roomId: roomId}, function(err) {
        if (!err) {
          $scope.refresChatMessages();
        }
      });
    }

    $scope.userNames = function() {
      var result = [];
      angular.forEach($window.users, function(usr) {
        result.push(usr);
      })
      return result;
    }

    $scope.userGroups = function() {
      var result = []
      for(var i in $window.users){
        for(var j in $window.users[i].section){
          if (result.indexOf($window.users[i].section[j]) == -1){
            result.push($window.users[i].section[j]);
          }
        }
      }
      return result;
    }

    $window.history = function(daysBefore, userId, scroll, callback) {
      dateFrom = '';
      userId = userId || $window.toUserId;
      if (daysBefore){
        dateFrom = new Date();
        dateFrom.setDate(dateFrom.getDate() - daysBefore);
      }
      socket.emit("getHistory", {userId: userId, dateFrom: dateFrom, onlyConf: $window.onlyConf}, function(messages) {
        $window.chatMessages[userId] = [];
        angular.forEach(messages, function(msg){
          $window.chatMessages[userId].push({user: {id: msg.from.id, name: msg.from.name}, message: msg.message, date: msg.date});  
        })
        if(!$scope.$$phase) $scope.$apply();
        if (callback) callback($window.chatMessages[userId]);
        // angular.element("#chatMessagesScrollable").scrollTop(angular.element("#chatMessagesScrollable")[0].scrollHeight);
      });
    }

    $window.leaveConf = function() {
      socket.emit('leaveRoom', {roomId: $window.toUserId}, function() {
        // socket.emit("confMessage", {from: $window.user.username, to: $window.toUserId, message: "Я покиндаю конференцию."}, function(){});
        delete $window.messageUsers[$window.toUserId];
        delete $window.confs[$window.toUserId];

        for(var i in $window.messageUsers){
          $window.toUserId = i;
          $window.toType = $window.messageUsers[i].type;
        }
        if (Object.keys($window.messageUsers).length == 0){
          $window.toUserId = "";
          $window.toType = "";
          $window.chatMessagesVisible='hide';
        }
        if(!$scope.$$phase) $scope.$apply();
        $scope.refresChatMessages();
      })
    }

    $scope.filterShowInGroup = function() {
      return function(usr) {
        return !$scope.online || (usr.status.id === 1 || usr.status.id === 2 || usr.unread_messages > 0);
      }
    }

    $scope.changeStatus = function(status) {
      text = '';
      if (status == 3) {
        status = 1;
        text = prompt("Вовлечен на... (%)", "") + "%";
      }else if(status == 2) {
        text = prompt("Отошел до... (время)", "");
      }else if(status == 0) {
        text = prompt("Отсутствую до... (дата/время)", "");
        if (text) text = "до " + text;
      }

      socket.emit('changeStatus', {id: status, text: text}, function() {
        $window.user.chatStatus = {id: status, text: text};
        $rootScope.$apply();
      });
    }

    $window.removeMessageUsers = function(id){
      delete $window.messageUsers[id];
      if (Object.keys($window.messageUsers).length == 0){
        $window.toUserId = "";
        $window.toType = "";
        $window.chatMessagesVisible='hide';
      }else{
        for(var i in $window.messageUsers){
          usr = $window.messageUsers[i];
          $scope.selectUser(i, usr.name, usr.type);
          break;
        }
      }
    }
    $scope.openModalCreateConf = function() {
      var modalInstance = $uibModal.open({templateUrl: 'modalCreateConf.html', controller: 'modalCreateConf', size: 'lg'});

      modalInstance.result.then(function(conf) {
        $window.chatCreateRoom(conf.name, function(roomId) {
          if (roomId) {
            angular.forEach(conf.usrs, function(checked, key) {
              if (checked) $window.addUserToConf(key, roomId);
            })
            $scope.selectUser(roomId, conf.name, 'Conf');
          }
        });

      });
    };
    $scope.countOfObject = function(obj) {
      return Object.keys(obj).length;
    }
    $scope.openChatWindow = function(focus) {
      if(!$scope.chatMessagesWindow || $scope.chatMessagesWindow.closed){
        $scope.chatMessagesWindow = $window.open($state.href('chat-messages'), 'chatMessages', 
        'left=410,top=0,resizable=yes,scrollbars=yes,status=yes,width=550,height=400,menubar=no,toolbar=no,location=no,directories=no');
      }else if(focus){
        $scope.chatMessagesWindow.focus();
      }
    }
    $scope.openChatHistory = function(withUser, onlyConf) {
      $window.historyWithUser = withUser;
      $window.onlyConf = onlyConf;
      if(!$scope.chatHistoryWindow || $scope.chatHistoryWindow.closed){
        $scope.chatHistoryWindow = $window.open($state.href('chat-history'), 'chatHistory', 
        'left=410,top=0,resizable=yes,scrollbars=yes,status=yes,width=550,height=400,menubar=no,toolbar=no,location=no,directories=no');
      }else if(focus){
        $scope.chatHistoryWindow.focus();
      }
    }
    $window.openChatWindow = function(withUser) {
      $scope.openChatHistory(withUser);
    }
    $scope.refresChatMessages = function(){
      if($scope.chatMessagesWindow && !$scope.chatMessagesWindow.closed && $scope.chatMessagesWindow.refresh){
        $scope.chatMessagesWindow.refresh();
      }
    }
  });
})
.filter('shortNameFilter', function() {
  return function(fullname) {
    fullnameArr = fullname.split(" ");
    res = fullnameArr[0];
    if (fullnameArr[1]){
      res += ' ' + fullnameArr[1].charAt(0) + '.';
    }
    return res;
  }
})

.controller('chatMessagesCtrl', function($scope, $window, $state, $timeout, $filter){
  self = this;
  $scope.context = {show:false};
  $scope.files = [];
  $scope.$window = $window;
  $scope.$state = $state;
  $scope.chat = {text:''};
  $scope.$chatWindow = $window.opener;
  $scope.sms = false;
  $window.document.title = "Окно чата Трето";
  angular.element('#messageText').focus();

  $scope.selectUser = function(id, name, type) {
    $scope.$chatWindow.selectUser(id, name, type);
    angular.element('#messageText').focus();
  }
  $scope.removeMessageUsers = function(id) {
    $scope.$chatWindow.removeMessageUsers(id);
    if (Object.keys($scope.$chatWindow.messageUsers).length == 0){
      $window.close();
    }
  }
  $window.refresh = function() {
    if(!$scope.$$phase) {
      $scope.$apply();
    }
  }
  $scope.scrollBottom = function() {
    $window.scrollBottom();
  }

  $window.scrollBottom = function() {
    angular.element("#chatMessagesScrollable").scrollTop(angular.element("#chatMessagesScrollable")[0].scrollHeight);
  }
  $scope.leaveConf = function() {
    $scope.$chatWindow.leaveConf();
    if (Object.keys($scope.$chatWindow.messageUsers).length == 0){
      $window.close();
    }
  }
  confSend = function() {
    text = $scope.chat.text;
    $scope.chat.text = '';
    $scope.$chatWindow.confSend(text, function() {
      if(!$scope.$$phase) $scope.$apply();
    });
  }
  guestSend = function() {
    text = $scope.chat.text;
    $scope.chat.text = '';
    $scope.$chatWindow.guestSend(text, function() {
      if(!$scope.$$phase) $scope.$apply();
    });
  }
  chatSend = function() {
    $scope.$chatWindow.sms = $scope.sms;
    text = $scope.chat.text;
    $scope.chat.text = '';
    $scope.$chatWindow.chatSend(text, function() {
      if ($scope.$chatWindow.sms){
        $scope.$chatWindow.chatMessages[$scope.$chatWindow.toUserId][$scope.$chatWindow.chatMessages[$scope.$chatWindow.toUserId].length - 1].message += "<br><br>/отправлено по SMS/";
      }
      $scope.$chatWindow.sms = false;
      $scope.sms = false;
      if(!$scope.$$phase) $scope.$apply();
    });
  }
  $scope.send = function() {
    $scope.chat.text = angular.element('#messageText').html();
    angular.element('#messageText').html('');
    if ($scope.$chatWindow.toType=='Conf'){
      confSend();
    }
    else if($scope.$chatWindow.toType=='guest'){
      guestSend()
    }
    else {
      chatSend();
    }
  }
  $scope.quote = function(message) {
    //(RE: 19:51  ком немой был, сейчас гляну )
    quoteStr = "RE: ";
    quoteStr += $filter('date')(message.date, "HH:mm") + " ";
    quoteStr += $filter('htmlToPlaintext')(message.message).substr(0,30)+"<div><br></div>";
    angular.element('#messageText').append(quoteStr);
    angular.element('#messageText').focus();
  }

  $scope.openInfo = function(id) {
    $window.open($state.href('body.profileDisplay', { id: id }),'_blank')
  }

  $scope.addUserToConf = function(id){
    $scope.$chatWindow.addUserToConf(id);
  }

  $scope.userNames = function() {
    var result = [];
    angular.forEach($scope.$chatWindow.users, function(usr) {
      result.push(usr);
    })
    return result;
  }

  $scope.$watch(function() { return $scope.files.pop(); }, function(newValue, oldValue) {
    if (newValue !== oldValue&&newValue){
      textLink = '<a href="'+newValue[0].link+'" target="blank">'+newValue[0].doc.originalFilename+'</a> ';
      if($scope.$chatWindow.toType=='Conf'){
        $scope.$chatWindow.confSend(textLink, function() {
          if(!$scope.$$phase) $scope.$apply();
        });
      }else if($scope.$chatWindow.toType=='guest'){
        $scope.$chatWindow.guestSend(textLink, function() {
          if(!$scope.$$phase) $scope.$apply();
        });
      }else{
        $scope.$chatWindow.chatSend(textLink, function() {
          if(!$scope.$$phase) $scope.$apply();
        });
      }
    }
  })

  $scope.showHistory = function() {
    $scope.$chatWindow.openChatWindow(true);
  }
  $scope.contextShow = function(e) {
    angular.element('#chat-context').css('left', e.pageX);
    angular.element('#chat-context').css('top', e.pageY);
    $scope.context.show=true;
  }

  focusWindowListeners = function(){
    var hidden = "hidden";

    // Standards:
    if (hidden in document)
      document.addEventListener("visibilitychange", onchange);
    else if ((hidden = "mozHidden") in document)
      document.addEventListener("mozvisibilitychange", onchange);
    else if ((hidden = "webkitHidden") in document)
      document.addEventListener("webkitvisibilitychange", onchange);
    else if ((hidden = "msHidden") in document)
      document.addEventListener("msvisibilitychange", onchange);
    // IE 9 and lower:
    else if ("onfocusin" in document)
      document.onfocusin = document.onfocusout = onchange;
    // All others:
    else
      window.onpageshow = window.onpagehide
      = window.onfocus = window.onblur = onchange;

    function onchange (evt) {
      var v = "visible", h = "hidden",
          evtMap = {
            focus:v, focusin:v, pageshow:v, blur:h, focusout:h, pagehide:h
          };

      evt = evt || window.event;
      if (evt.type in evtMap)
        $scope.$chatWindow.focusChatWindow = evtMap[evt.type];
        // document.body.className = evtMap[evt.type];
      else
        $scope.$chatWindow.focusChatWindow = this[hidden] ? "hidden" : "visible";
        // document.body.className = this[hidden] ? "hidden" : "visible";
      console.log($scope.$chatWindow.focusChatWindow);
    }

    // set the initial state (but only if browser supports the Page Visibility API)
    if( document[hidden] !== undefined )
      onchange({type: document[hidden] ? "blur" : "focus"});
  }
  focusWindowListeners();

})

.controller('chatHistoryCtrl', function($scope, $window, $state, $timeout){
  $scope.$chatWindow = $window.opener;
  $scope.messages = [];
  $scope.menuList = [
      {name: "1 день", days: 1},
      {name: "10 дней", days: 10},
      {name: "30 дней", days: 30},
      {name: "6 месяцев", days: 180},
      {name: "1 год", days: 365},
      {name: "Старше года", days: 10000},
    ];

  $scope.getHistory = function(days) {
    var user = 'all';
    if ( $scope.$chatWindow.historyWithUser) {
      user =  $scope.$chatWindow.toUserId;
      $window.document.title = "История разговора с "+($scope.$chatWindow.users[$scope.$chatWindow.toUserId]||$scope.$chatWindow.guests[$scope.$chatWindow.toUserId]).name;
    }else{
      if ($scope.$chatWindow.onlyConf){
        $window.document.title = "История разговора во всех конференциях";
      }else{
        $window.document.title = "История разговора со всеми сотрудниками.";
      }
    }
    console.log('history with '+user+" "+days+" days, onlyConf: " +$scope.$chatWindow.onlyConf);
    
    $scope.$chatWindow.history(days, user, false, function(mesgs) {
      $scope.messages = mesgs;
      if(!$scope.$$phase) $scope.$apply();
    });
  }
  $scope.getHistory(1);
})

.controller('modalCreateConf', function($scope, $uibModalInstance, $rootScope, $window) {
  $scope.$window = $window;
  $scope.confName = 'Конференция';
  $scope.checkedUsrs = {};
  $scope.ok = function() {
    $uibModalInstance.close({name: $scope.confName, usrs: $scope.checkedUsrs});
  }
  $scope.cancel = function () {
    $uibModalInstance.dismiss('cancel');
  };
})

.factory('Socket', function($cookies){
  var obj = {
    sock: undefined,
    get: function(callback) {
      if (!obj.sock)
        obj.sock = new io(treto_io_host+'/?PHPSESSID='+$cookies.get('PHPSESSID'), {multiplex: false});
      callback(obj.sock);
    },
    logout: function() {
      obj.sock.destroy();
      delete obj.sock;
    }
  };
  return obj;
});
