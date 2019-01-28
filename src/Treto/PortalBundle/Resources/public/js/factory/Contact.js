portalApp
        .factory('Contacts', contacts)
        .factory('Contact', contact)

function contact($http, Popup, $state, $rootScope) {
  var Contact = function (sp, callback) {
    var self = this;
    self.busy = true;

    this.Group = [];
    this.validate = function () {
      valid = true;
      error = [];
      var mandatoryFields = [];
      if (self.DocumentType == 'Person')
        mandatoryFields.push('LastName');
      (mandatoryFields).forEach(function (key) {
        valid = valid && self[key];
        if (self[key] == undefined)
          error.push(key + ' is missing');
      });
      if (error.length) {
        new Popup('Ошибки валидации', error.join('\n'), 'error');
      }
      return valid;
    }
    this.persist = function (notGo, cb) {
      if (!self.AutoDicomposition && !self.validate()) {
        return false;
      }
      self.prepareForWrite(self._id);
      self.saved = false;
      self.C1WaitSync = true;
      var contact = this;
      $http({method: 'POST', url: 'api/contact/save', data: {'contact': this}})
              .then(function (response) {
                self.saved = true;
                var responseContact = response;
                if (response.data.success && !notGo) {
                  var current = {};
                  current._id = 0;
                  current.form = 'message';
                  current.ParentDbName = 'Contacts';
                  current.status = 'open';
                  current.body = contact.textAuto;
                  current.subject = 'Резюме';
                  current._meta = null;
                  current.Author = $rootScope.user.username;
                  current.AuthorRus = $rootScope.user.portalData.LastName + " " + $rootScope.user.portalData.name;
                  current.parentID = responseContact.data.response.unid;
                  current.subjectID = responseContact.data.response.unid;

                  var goParam = {id: responseContact.data.response.unid, "type": "contact"};
                  if($state.current.name == 'body.1cEditPerson' || $state.current.name == 'body.1cEditOrganization'){
                    goParam['client'] = '1C';
                  }
                  
                  if (self.AutoDicomposition) {
                    $http({method: 'POST', url: 'api/discussion/set/0', data: {'document': current}})
                            .then(function (response) {
                              if (response.data.success) {
                                $state.go('body.discus', goParam, {reload: true});
                              } else {
                                new Popup('Discus', response.data.message, 'error');
                              }
                            }, httpErrorHandler);
                  }
                  else {
                    $state.go('body.discus', goParam, {reload: true});
                  }
                }
                if(typeof cb != "undefined"){
                  cb();
                }
              }, function (data, status) {
                httpErrorHandler(data, status);
              });
    };

    this.persistDiscount = function (parentContactId) {
      self.prepareForWriteDiscount(parentContactId);
      self.saved = false;
      $http({method: 'POST', url: 'api/contact/save', data: {contact: this}})
        .then(function (response) {
          self.saved = true;
          if (response.data.success) {
            this.discExist = false;
            angular.forEach($rootScope.discounts, function(d, key) {
              if (d.unid == response.data.response.unid) this.discExist = key;
            })
            if (this.discExist) {
              $rootScope.discounts[this.discExist] = response.data.response;
            }else{
              $rootScope.discounts.push(response.data.response);
            }
          }
        }, function (data, status) {
          httpErrorHandler(data, status);
        });
    };

    this.delete = function () {
      var sure = confirm('Вы уверены что будете удалять', 'Контакты');
      if (!sure)
        return;
      $http({method: 'GET', url: 'api/contact/delete', params: {id: self.id}})
              .then(function (response) {
                self.saved = true;
                if (response.data.Status == 'deleted') {
                  $state.go('body.contacts');
                  new Popup('Contacts', 'contacts.removed', 'notify');
                }
              }, httpErrorHandler);
    };
    this.permanentDelete = function () {
      var sure = confirm('Вы уверены? Контак будет уже невозможно восстановить', 'Контакты');
      if (!sure)
        return;
      $http({method: 'GET', url: 'api/contact/permanentDelete', params: {id: self.id}})
              .then(function (response) {
                console.log(response);
                if (response.data.result) {
                  $state.go('body.contacts');
                  new Popup('Contacts', 'contacts.removed', 'notify');
                }
              }, httpErrorHandler);
    };
    this.contactUndelete = function (id) {
      var sure = confirm('Вы уверены что хотите восстановить', 'Контакты');
      if (!sure)
        return;
      $http({method: 'GET', url: 'api/contact/undelete', params: {id: self.id}})
              .then(function (response) {
                if (response.data.Status === 'open') {
                  $state.go('body.contacts');
                  new Popup('Контакты', 'Контакт восстановлен', 'notify');
                }
              }, httpErrorHandler);
    }

    this.initialize = function (cb) {
      var addr = 'api/contact/item/' + sp.id;
      $http({method: 'GET', url: addr})
              .then(function (response) {
                angular.extend(self, response.data);
                if (cb)
                  cb();
              })
    };

    this.getMain = function () {
      return this.main_doc;
    };

    this.prepareForWrite = function (contactId) {
      if (!contactId) {
        if (self.PhoneCellValues){
          self.PhoneCellValues = self.clearEmptyItems(self.PhoneCellValues);
        }
        if (self.PhoneValues){
          self.PhoneValues = self.clearEmptyItems(self.PhoneValues);
        }
        if (self.EmailValues){
          self.EmailValues = self.clearEmptyItems(self.EmailValues);
        }
        self.Author = $rootScope.user.username;
        self.AuthorRus = $rootScope.user.portalData.LastName + " " + $rootScope.user.portalData.name;
        self.AuthorCommon = $rootScope.user.portalData.FullName;
      }
    };

    this.clearEmptyItems = function (arr)
    {
      var arrTmp = [];
      arr.forEach(function (v) {
        v = $.trim(v);
        if (v && v !== '' && v !== null)
        {
          arrTmp.push(v);
        }
      });
      return arrTmp;
    }

    this.prepareForWriteDiscount = function (parentContactId, contactId) {
      if (contactId)//EDIT
      {

      } else//NEW
      {
        self.ContactId = parentContactId;
        self.Author = [];
        self.Author.push($rootScope.user.portalData.FullName);
      }
    };

    this.addPackage = function () {//todo:revrite addEmptyField
      if (angular.isUndefined(self.PackageMeasureUnit))
      {
        self.PackageMeasureUnit = [];
      }
      if (angular.isUndefined(self.PackageSize))
      {
        self.PackageSize = [];
      }
      if (angular.isUndefined(self.PackageFactor))
      {
        self.PackageFactor = [];
      }
      self.PackageMeasureUnit.push('');
      self.PackageSize.push('');
      self.PackageFactor.push('');
    };

    this.addEmptyField = function (field) {
      if (angular.isUndefined(self[field]))
      {
        self[field] = [];
      }
      self[field].push('');
    };

    this.removePackage = function (key) {
      self.PackageMeasureUnit.splice(key, 1);
      self.PackageSize.splice(key, 1);
      self.PackageFactor.splice(key, 1);
    };

    this.clearFields = function (fields) {
      if (!Array.isArray(fields))
      {
        return false;
      }
      var len = fields.length,
              i = 0;
      for (i; i < len; i = i + 1) {
        var field = fields[i];
        if (!angular.isUndefined(self[field]))
        {
          self[field] = '';
        }
      }
    }

    if (sp.id) {
      this.initialize(function () {
        if (callback)
          callback();
      });
    }
    else {
      angular.extend(self, sp);
      if (callback)
        callback();
    }
  }

  return Contact;
}
function contacts(BatchHttp) {
  return function (query, offset, limit, res) {
    BatchHttp({method: 'GET', url: 'api/contact/list', params: {limit: limit, offset: offset, query: query}}).
            then(function (response) {
              res(response.data);
            });
  }
}