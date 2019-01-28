var mainApp = angular.module('mainApp', [
    'ngSanitize',
    'ngCookies',
    'ui.router',
    'ui.bootstrap',
    'infinite-scroll',
    'portalApp',
    'authApp',
    'angularFileUpload',
    'angular.filter',
    'xml',
    'yaMap',
    'textAngular',
    'ui.select',
    'ngMessages',
    '720kb.datepicker',
    'ui.tinymce',
    'daterangepicker',
    'ngMaterial',
    'ui.calendar',
    'angular-rich-text-diff'
]);

mainApp.config(function($mdDateLocaleProvider) {
  // This is configurtion of md-datepicker for ru locale
  $mdDateLocaleProvider.firstDayOfWeek = 1;
  $mdDateLocaleProvider.formatDate = function(str) {
    if (!str) return '';
    return new Date(str).toLocaleDateString();
  };
  $mdDateLocaleProvider.parseDate = function(viewValue) {
    var valueArr = viewValue.split( ~viewValue.indexOf('.') ? '.' : ( ~viewValue.indexOf('-') ? '-' : '/' ));
    if (valueArr.length > 1) {
      var t = valueArr[0];
      valueArr[0] = valueArr[1];
      valueArr[1] = t;
      return new Date(valueArr.join('/'));
    } else return new Date(NaN);
  };
});

mainApp.run(function($rootScope, $anchorScroll) {
  $anchorScroll.yOffset = 50;
  $rootScope.convertObjDateToStr = function(dateObj) { //20151218
    if (dateObj) {
      if (typeof dateObj === 'string')
        dateObj = new Date(dateObj);
    }else{
      dateObj = new Date();
    }
    return dateObj.getFullYear() + ('0' + (dateObj.getMonth() + 1)).slice(-2) + ('0' + dateObj.getDate()).slice(-2);
  }
  $rootScope.convertStrToObj = function(str) {
    if (!str) return new Date();
    return new Date(str.substr(0,4), str.substr(4,2)*1-1, str.substr(6,2));
  }
  $rootScope.convertStrISOToObj = function(str) { //20160216T141247
    if (!str) return new Date();
    return new Date(str.substr(0,4), str.substr(4,2)*1-1, str.substr(6,2), str.substr(9,2), str.substr(11,2), str.substr(13,2));
  }
  $rootScope.convertReadableStrToObj = function(str) { //29.05.2017 14:06:25
    if (!str) return new Date();
    return new Date(str.substr(6,4), str.substr(3,2)*1-1, str.substr(0,2), str.substr(11,2), str.substr(14,2), str.substr(17,2));
  }
  $rootScope.convertStrToLocaleDate = function(str) {
    if (!str || str.length == 0 || typeof str !== 'string') return new Date().toLocaleDateString();
    return new Date(str.substr(0,4), str.substr(4,2)*1-1, str.substr(6,2)).toLocaleDateString();
  }
  $rootScope.formatDateForReadBy = function(dateObj, ISO) { //ISO ? '20160303T134931' : '03.03.2016 13:49:31'
    if (!dateObj) return false;

    var date = new Date(dateObj.getTime());
    date.setHours(date.getHours() + 3); //Moscow timezone
    var res = ''
    if (!ISO) {
      res = ('0' + date.getUTCDate()).slice(-2)+'.'+('0' + (date.getUTCMonth() + 1)).slice(-2)+'.'+date.getUTCFullYear()+
    ' '+('0' + date.getUTCHours()).slice(-2)+':'+('0' + date.getUTCMinutes()).slice(-2)+':'+('0' + date.getUTCSeconds()).slice(-2);
    } else {
      res = ''+date.getUTCFullYear()+('0' + (date.getUTCMonth() + 1)).slice(-2)+('0' + date.getUTCDate()).slice(-2)+
    'T'+('0' + date.getUTCHours()).slice(-2)+('0' + date.getUTCMinutes()).slice(-2)+('0' + date.getUTCSeconds()).slice(-2);
    }
    return res;
  }
  $rootScope.localizeDate = function(str) {
    if (!str) return new Date().toLocaleDateString();
    return new Date(str).toLocaleDateString();
  }
  $rootScope.viewToDate = function(viewValue) {
    return new Date(viewValue);
  }
})

var httpErrorHandler = null;

Object.defineProperty(Object.prototype, "isEmpty", {
    value: function() { for (var k in this) return false; return true },
    writable: true,
    enumerable: false,
    configurable: true
});

// Array Remove - By John Resig (MIT Licensed)
Object.defineProperty(Array.prototype, "remove", {
    value: function(from,to) {
      var rest = this.slice((to || from) + 1 || this.length);
      this.length = from < 0 ? this.length + from : from;
      return this.push.apply(this, rest);
    },
    writable: true,
    enumerable: false,
    configurable: true
});

// Array range by http://stackoverflow.com/users/223274/ian-henry
Object.defineProperty(Array.prototype, "range", {
    value: function(start,count,step) {
      step = step || 1;
      if(!count) {
        count = start;
        start = 0;
      }
      var foo = [];
      for (var i = 0; i < count; i+=step) {
          foo.push(start + i);
      }
      return foo;
    },
    writable: true,
    enumerable: false,
    configurable: true
});

Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

function isArray(str) {
    return Object.prototype.toString.call(str) === '[object Array]';
}

function isTypeOf(str, tOf) {
    tOf = tOf || 'object';
    return typeof str === tOf;
}

mainApp.factory('TretoGlobal', function($rootScope, TretoDateTime) {
  var self = this;

  self.init = function(){

  };

  return self;
});
