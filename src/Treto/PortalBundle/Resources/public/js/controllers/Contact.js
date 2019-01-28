portalApp
        .controller('contactsCtrl', contactsCtrl)
        .controller('contactNewPersonCtrl', contactNewPersonCtrl)
        .controller('contactNewPersonAutoCtrl', contactNewPersonAutoCtrl)
        .controller('contactNewOrganizationCtrl', contactNewOrganizationCtrl)
        .controller('modalEditContact', modalEditContact)

function contactsCtrl ($http, $scope, $rootScope, $state, $location, Contacts, Dictionary, ProfileList, AutoComplete) {
  var thistype = $state.params.thistype;
  $scope.status = "Подождите...";
  $scope.nameValue = {name: "В имени", search: ["ContactName", "MainName", "OtherName", "Full Name"]};
  $scope.managerValue = {name: "По менеджеру фабрики", search: "manager"};
  $scope.bmValue = {name: "По БМ", search: "ResponsibleManager"};
  $scope.contactsBusy = true;
  $scope.contacts = [];
  $scope.xn = {};
  $scope.for1c = $state.current.name=='body.1cContactList';
  $rootScope.s = {'type': $scope.nameValue };
  $scope.selectedGroup = 'contacts';

  $scope.DieIsCast = '';
  $scope.selectedContact = false;
  $scope.SelectedContacts_IDS = false;
  $scope.autoComplete = new AutoComplete();
  $scope.loading = true;
  $scope.allContCollapsed = true;
  $scope.collCollapsed = true;

  var params = $location.search();

  $scope.getContactUrlParams = function(unid){
    var result = {id: unid, "type": "contact"};
    if(typeof params['client'] != 'undefined'){
      result['client'] = params['client'];
    }
    return result;
  };

  $scope.removeParams = function(){
    $scope.setDefault();
    $scope.runSearch();
  };

  $scope.setDefault = function(){
    $rootScope.s.text = undefined;
    $rootScope.s.group = undefined;
    $rootScope.s.contact = undefined;
    $rootScope.p = undefined;
  };

  $scope.changeTab = function(tabName){
    if(!$scope.loading){
      $scope.selectedTab = tabName;
      switch(tabName){
        case 'allEmpls':
          $scope.setDefault();
          $scope.collCollapsed ? $scope.getProfiles('', '', function () {
            $scope.collCollapsed = !$scope.collCollapsed;
          }) : '';
          $scope.allContCollapsed = true;
          $scope.changeSelectedGroup('empls');
          break;
        case 'allContact':
          $scope.allContCollapsed = !$scope.allContCollapsed;
          $scope.collCollapsed = true;
          $scope.changeSelectedGroup('contacts');
          break;
        case 'basket':
          $rootScope.s = {deleted: true,Status:'deleted'};
          $rootScope.getContacts();
          break;
        case 'allDiss':
          $scope.changeSelectedGroup('dismisse', true);
          break;
      }
    }
  };

  $scope.runSearch = function(){
    if($scope.selectedGroup == 'empls'){
      $scope.getProfiles($rootScope.p, $rootScope.s.text.replace(/\/|\\/g,"\\"));
    }
    else if($scope.selectedGroup == 'contacts'){
      $rootScope.getContacts();
    }
    else {
      $scope.getDismissed($rootScope.s.text);
    }
  };

  $scope.getAutoCompleteList = function(value) {
    var response = [];

   if($scope.selectedGroup == 'contacts'){
      response = $scope.autoComplete.contacts({text:value});
    }
    else {
      response = $scope.autoComplete.profile($rootScope.s.text, $rootScope.p, 'FullNameInRus');
    }
    return response;
  };

  $scope.changeSelectedGroup = function(group, runSearch){
    $scope.setDefault();
    $scope.selectedGroup = group;
    if(runSearch){
      $scope.runSearch();
    }
  };

  if(typeof params.IDs != 'undefined'){
    $http({method: 'GET', url: 'api/discussion/get/'+params.IDs, params: {"is-contact":true} })
        .then(function(response) {
          if(response.data && response.data.success){
            $scope.select1cContact(response.data.document);
          }
        }, httpErrorHandler);
  }

  $scope.select1cContact = function(contact){
    $scope.selectedContact = {
      name:contact.ContactName||contact.subject||contact.LastName,
      unid:contact.unid
    };
  };

  $scope.save1cContacts = function () {
    $scope.SelectedContacts_IDS = [$scope.selectedContact.unid];
    $scope.DieIsCast = 1;
  };

  $scope.reset1cContacts = function () {
    $scope.SelectedContacts_IDS = false;
    $scope.SelectedContacts_IDS = false;
    $scope.DieIsCast = 2;
  };

  switch (thistype) {
    case 'organization':
      $rootScope.s.contact = {name:'Компании', search:'Organization'};
      break;
    case 'resume':
      $rootScope.s.group = {name: "Минирезюме с сайта", search: "62F4B560590E6719C3257899003B3EA2"};
      break;
  }

  $scope.statusListDict = new Dictionary('StatusList', true, false, true);
  $scope.sectionDict = new Dictionary('Section', true);

  Contacts( $rootScope.s, 0, 20, function(conts) {
    switch (thistype) {
      case 'organization':
        $rootScope.s.contact = {name:'Компании', search:'Organization'};
        break;
      case 'resume':
        $rootScope.s.group = {name: "Минирезюме с сайта", search: "62F4B560590E6719C3257899003B3EA2"};
        break;
    }
    $scope.loading = false;
    $scope.contacts = conts;
    $scope.page = 20;
    $scope.contactsBusy = false;
    if ($scope.contacts.length > 0){
      $scope.status = "";
    }else{
      $scope.status = "Ничего не найдено.";
    }
  });

  $rootScope.getContacts = function() {
    $scope.contacts = [];
    $scope.status = "Подождите...";
    $scope.contactsBusy = true;
    $scope.loading = true;
    Contacts( $rootScope.s, 0, 19, function(conts) {
      $scope.loading = false;
      $scope.contacts = conts;
      $scope.page = 20;
      $scope.contactsBusy = false;
      if ($scope.contacts.length > 0){
        $scope.status = "";
      }else{
        $scope.status = "Ничего не найдено.";
      }
    })
  };

  $scope.getProfiles = function(section, name, callback) {
    $scope.loading = true;
    $scope.contacts = [];
    $scope.status = "Подождите...";
    $scope.contactsBusy = true;
    $rootScope.p = section;
    ProfileList.findUsersBySection(section, name, function(users) {
      if(callback){
        callback();
      }
      $scope.loading = false;
      $scope.contacts = users;
      if ($scope.contacts.length > 0){
        $scope.status = "";
      }else{
        $scope.status = "Ничего не найдено.";
      }
    })
  };

  $scope.getDismissed = function() {
    $scope.contacts = [];
    $scope.status = "Подождите...";
    $scope.contactsBusy = true;
    $rootScope.p = 'Уволенные';
    $scope.loading = true;
    ProfileList.findUsersDismissed($rootScope.s.text, function(users) {
      $scope.loading = false;
      $scope.contacts = users;
      if ($scope.contacts.length > 0){
        $scope.status = "";
      }else{
        $scope.status = "Ничего не найдено.";
      }
    })
  };

  $scope.getBMs = function() {
    $scope.BMs = [];
    $scope.BMstatus = "Подождите...";
    $scope.loading = true;
    ProfileList.findUsersBySection('БМ', null, function(users) {
      $scope.BMs = users;
      $scope.loading = false;
      if ($scope.BMs.length > 0){
        $scope.BMstatus = "";
      }else{
        $scope.BMstatus = "Ничего не найдено.";
      }
    })
  };

  $scope.addContacts = function(){
    if ($scope.contactsBusy) return;
    $scope.contactsBusy = true;
    $scope.status = "Подождите...";
    Contacts( $rootScope.s, $scope.page, 19, function(conts) {
      angular.forEach(conts, function (value, key) {
        $scope.contacts.push(value);
      })
      if ($scope.contacts.length > 0){
        $scope.status = "";
      }else{
        $scope.status = "Ничего не найдено.";
      }
      $scope.page += 20;
      if (conts.length > 0){
        $scope.contactsBusy = false;
      }
    })
  }

  $scope.getTrash = function() {
    $rootScope.s.Status = 'deleted';
    $rootScope.getContacts();
  }

  $scope.allContacts = function() {
    $rootScope.s = {'type': $scope.nameValue };
    $rootScope.getContacts();
  }

  $scope.contactUndelete = function(id) {
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
}

function modalEditContact($scope, $rootScope, Contact, Dictionary,
        Profile, AutoComplete, MultiselectHelper, Discus, Security, $stateParams, $state, $filter) {
  $scope.multiselectHelper = new MultiselectHelper();
  $scope.autoComplete = new AutoComplete();
  $scope.profile = new Profile();
  $scope.status = "Подождите...";
  $scope.busy = true;
  $scope.contactBusy = true;
  $scope.statusListDict = new Dictionary('StatusList', true, false, true);
  $scope.positionsDict = new Dictionary('Positions', true, false, true);
  $scope.sectionDict = new Dictionary('Section', true);
  $scope.companyNameDict = new Dictionary('companyName', true);
  $scope.currencyDict = new Dictionary('Currency', true);
  $scope.infoSourceDict = new Dictionary('InformationSourceCatalog', true, false, true);
  $scope.organizationCountries = new Dictionary('Country', true);
  $scope.contactLanguages = new Dictionary('Languages', true);
  $scope.error = {};
  $scope.payMethod = [];
  $scope.payMethodList = {
    1:'Оплата всего по готовности',
    2:'Предоплата всего',
    3:'Предоплата только производства'
  };

  var security = false;

  $scope.changeIndividuallySamples = function(param){
    var indexOf = $scope.contact.individuallySamples.indexOf(param);
    $scope[param] = indexOf;
    if(indexOf > -1){
      $scope.contact.individuallySamples.splice(indexOf, 1);
    }
    else {
      $scope.contact.individuallySamples.push(param);
    }
  };

  $scope.dropdownCheckboxChange = function(val, action, field){
    if(!action){
      $scope.contact[field].splice($scope.contact[field].indexOf(val), 1);
    }
    else {
      $scope.contact[field].push(val);
    }
    return action;
  };

  $scope.changeBM = function(newResponsibleManager){
    if(typeof newResponsibleManager.login !== 'undefined'){
      var security = new Security($scope.contact);
      if(!security.hasPrivilege('write', newResponsibleManager.login)){
        security.addPrivilege('write', 'username', newResponsibleManager.login)
      }
    }
  };

  $scope.changeHoliday = function(key, index){
    key = key == 'inHoliday'?'inHoliday':'outHoliday';
    var keyView = key == 'inHoliday'?'inHolidayView':'outHolidayView';

    $scope.contact[key][index] = $rootScope.convertObjDateToStr($scope[keyView][index]);
    $scope[keyView][index] = $rootScope.localizeDate($scope[keyView][index]);
  };

  var contactDoc = $stateParams.contactId?{id:$stateParams.contactId}:Discus.main_doc;
  $scope.contact = new Contact(contactDoc, function () {
    security = new Security($scope.contact);
    $scope.contact.isPublic = security.hasPrivilege('read','all') ? true : false;

    if($scope.contact.DocumentType == "Organization" &&
        (!$scope.contact.ContactWorkFrom || !$scope.contact.ContactWorkFrom.length)){
      $scope.contact.ContactWorkFrom = [''];
      $scope.contact.ContactWorkFromID = [''];
    }

    if(!$scope.contact.PhoneValues){
      $scope.contact.PhoneValues = [];
    }

    if(!$scope.contact.individuallySamples){
      $scope.contact.individuallySamples = [];
    }

    if(!$scope.contact.Rank){
      $scope.contact.Rank = [];
    }

    if(!$scope.contact.banApi){
      $scope.contact.banApi = false;
    }

    if(!angular.isArray($scope.contact.Rank)){
      $scope.contact.Rank = [$scope.contact.Rank];
    }
    $scope.contact.PhoneValues.push('');

    if(!$scope.contact.EmailValues){
      $scope.contact.EmailValues = [];
    }
    $scope.contact.EmailValues.push('');

    if(!$scope.contact.SiteValues){
      $scope.contact.SiteValues = [];
    }
    $scope.contact.SiteValues.push('');

    if(!$scope.contact.PhoneCellValues){
      $scope.contact.PhoneCellValues = [];
    }
    $scope.contact.PhoneCellValues.push('');

    $scope.contact.inHoliday = contactDoc.inHoliday&&contactDoc.inHoliday.length > 0?contactDoc.inHoliday:[''];
    $scope.contact.outHoliday = contactDoc.outHoliday&&contactDoc.outHoliday.length > 0?contactDoc.outHoliday:[''];
    $scope.inHolidayView = [];
    $scope.outHolidayView = [];

    for (var i in $scope.contact.inHoliday) {
      $scope.inHolidayView[i]= $scope.contact.inHoliday[i] ? $rootScope.convertStrToLocaleDate($scope.contact.inHoliday[i]) : '';

    }
    for (var i in $scope.contact.inHoliday) {
      $scope.outHolidayView[i]= $scope.contact.outHoliday[i] ? $rootScope.convertStrToLocaleDate($scope.contact.outHoliday[i]) : '';
    }
    
    $scope.allowAdding = function() {
      return $scope.contact.inHolidayView[$scope.contact.inHolidayView.length-1] &&
        $scope.contact.inHolidayView[$scope.contact.inHolidayView.length-1].length > 0 &&
        $scope.contact.outHolidayView[$scope.contact.outHolidayView.length-1] &&
        $scope.contact.outHolidayView[$scope.contact.outHolidayView.length-1].length > 0
    };
    $scope.dateBirthView = $rootScope.convertStrISOToObj($scope.contact.BirthDay)
    $scope.dateBirthView = $rootScope.localizeDate($scope.dateBirthView);
  });

  $scope.addLine = function() {
    if ($scope.contact.inHoliday[$scope.contact.inHoliday.length-1] && $scope.contact.outHoliday[$scope.contact.outHoliday.length-1]) {    
      $scope.contact.inHoliday[$scope.contact.inHoliday.length] = '';
      $scope.contact.outHoliday[$scope.contact.outHoliday.length] = '';
      $scope.contact.inHolidayView[$scope.contact.inHolidayView.length] = '';
      $scope.contact.outHolidayView[$scope.contact.outHolidayView.length] = '';
    }
  }
  $scope.changeInValue = function(i) {
    if ($scope.contact.inHolidayView[i].length > 0) {
      $scope.contact.inHoliday[i] = $rootScope.convertObjDateToStr($scope.contact.inHolidayView[i]);
      $scope.contact.inHolidayView[i]=$rootScope.localizeDate($scope.contact.inHolidayView[i]);
    } else {
      $scope.contact.inHoliday[i] = '';
    }
  }
  $scope.changeOutValue = function(i) {
    if ($scope.contact.outHolidayView[i].length > 0) {
      $scope.contact.outHoliday[i] = $rootScope.convertObjDateToStr($scope.contact.outHolidayView[i]);
      $scope.contact.outHolidayView[i]=$rootScope.localizeDate($scope.contact.outHolidayView[i])
    } else {
      $scope.contact.outHoliday[i] = '';
    }
  }

  $scope.discus = Discus;
  $scope.discus.current = $scope.contact;

  $scope.busy = false;
  $scope.status = "";
  $scope.setConditions = setConditions;

  $rootScope.$watch('contactName', function (v) {
    if (v)
    {
      $scope.contact.Company = v;
    }
  });
  $rootScope.$watch('EmployeeName', function (v) {
    if (v)
    {
      var old = '';
      if ($scope.contact.Employee) {
        old = $scope.contact.Employee;
        if ($scope.contact.Employee.length) {
          v = ', ' + v;
        }
      }
      $scope.contact.Employee = old + v;
    }
  });
  function setConditions(parent, newObj) {
    if (!parent || !newObj)
    {
      return false;
    }
    parent.MinDeliveryTerm = newObj.MinDeliveryTerm;
    parent.MinOrderSum = newObj.MinOrderSum;
    parent.IsPackageOnly = newObj.IsPackageOnly;
    parent.IsPackageWithConditions = newObj.IsPackageWithConditions;
    parent.PackageMeasureUnit = newObj.PackageMeasureUnit;
    parent.PackageSize = newObj.PackageSize;
    parent.PackageFactor = newObj.PackageFactor;
    parent.PayType = newObj.PayType;
    parent.PayCreditSum = newObj.PayCreditSum;
    parent.PayPrepayment = newObj.PayPrepayment;
    parent.PayAfterInvoiceTerm = newObj.PayAfterInvoiceTerm;
    parent.PrepaymentDiscount = newObj.PrepaymentDiscount;
    parent.IsIrregularFabric = newObj.IsIrregularFabric;
    parent.CallCost = newObj.CallCost;
    parent.ExportDeclarationCost = newObj.ExportDeclarationCost;
    parent.inHoliday = newObj.inHoliday;
    parent.outHoliday = newObj.outHoliday;
  }

  $scope.ok = function () {
    if($scope.contact.Organization && $scope.contact.Organization.length && !$scope.contact.OrganizationID.length){
      $scope.error.orgId = true;
    }
    else if($scope.contact.Salary && (typeof Number($scope.contact.Salary) != 'number' || isNaN(Number($scope.contact.Salary)))){
      $scope.error.Salary = true;
    }
    else if(!$filter('emailValidation')($scope.contact.EmailValues)){
      $scope.error.Email = true;
    }
    else {
      var personCountry = $scope.contact.DocumentType == "Person" && (typeof $scope.contact.Country == 'undefined' || !$scope.contact.Country) &&
          ($scope.contact.ContactStatus.indexOf(14) !== -1 || $scope.contact.ContactStatus.indexOf('14')) !== -1;
      var OrgCountry = $scope.contact.DocumentType == "Organization" && (typeof $scope.contact.Country == 'undefined' || !$scope.contact.Country);

      if (!$scope.contact.ContactStatus.length || personCountry || OrgCountry) {
        return false;
      }

      for (var i in $scope.contact.inHoliday) {
        if ((!$scope.contact.inHoliday[i] || !$scope.contact.outHoliday[i]) ||
            ($scope.contact.inHoliday[i].length < 6 || $scope.contact.outHoliday[i].length < 6) ||
            ($scope.contact.inHoliday[i] == 'NaNaNaN' || $scope.contact.outHoliday[i] == 'NaNaNaN')) {
          $scope.contact.inHoliday.splice(i, 1);
          $scope.contact.outHoliday.splice(i, 1);
        }
      }

      if($scope.contact.PayType == '2' && $scope.contact.PayIsDelayPresent == '0' && !$scope.contact.PayPrepayment){
        $scope.contact.PayPrepayment = 100;
      }

      Discus.hideEditForm();
      if(security){
        if ($scope.discus.current.isPublic && !security.hasPrivilege('read', 'all')) {
          security.addPrivilege('read', 'role', 'all');
        }
        else if (!$scope.discus.current.isPublic && security.hasPrivilege('read', 'all')) {
          security.removePrivilege('read', 'role', 'all');
        }
      }
      if(Discus.tempParticipants){
        for(var i in Discus.tempParticipants) {
           Discus.addParticipant(Discus.tempParticipants[i], 'username', $scope.contact);
        }
      }
      $scope.contact.persist();
    }
  };

  $scope.close = function () {
    if($state.current.name == 'body.1cEditPerson' || $state.current.name == 'body.1cEditOrganization'){
      $state.go('body.discus', {"id": $scope.contact.unid, "type": "contact", "client":"1C"}, {reload: true});
    }
    else {
      Discus.current = {};
      Discus.hideEditForm();
    }
  }
}

function contactNewPersonCtrl($scope, $rootScope, $state, Contact, Dictionary,
        Profile, AutoComplete, MultiselectHelper, Discus, Security, $location, $filter) {
  $scope.multiselectHelper = new MultiselectHelper();
  $scope.autoComplete = new AutoComplete();
  $scope.profile = new Profile();
  $scope.busy = true;
  $scope.status = "Подождите...";
  $scope.statusListDict = new Dictionary('StatusList', true, false, true);
  $scope.positionsDict = new Dictionary('Positions', true, false, true);
  $scope.sectionDict = new Dictionary('Section', true);
  $scope.companyNameDict = new Dictionary('companyName', true, false, true);
  $scope.currencyDict = new Dictionary('Currency', true);
  $scope.infoSourceDict = new Dictionary('InformationSourceCatalog', true, false, true);
  $scope.organizationCountries = new Dictionary('Country', true);
  $scope.contactLanguages = new Dictionary('Languages', true);
  var is1C = false;
  var search = $location.search();

  if(typeof search.organization != "undefined" && typeof search.organizationId != "undefined"){
    $scope.linkToOrganization = search;
    is1C = true;
  }

  $scope.contact = new Contact({
    'DocumentType': 'Person',
    'status': 0,
    'Group': [],
    'ContactStatus': [],
    'Rank': [],
    'section': [],
    'isPublic' : true,
    'Organization' : $scope.linkToOrganization?[$scope.linkToOrganization.organization]:[],
    'OrganizationID' : $scope.linkToOrganization?[$scope.linkToOrganization.organizationId]:[]
  });

  if($scope.linkToOrganization){
    $scope.inputOrganization = $scope.linkToOrganization.organization;
  }

  $scope.busy = false;
  $scope.status = "";
  $scope.discus = Discus;
  var security = new Security($scope.contact);

  $scope.dropdownCheckboxChange = function(val, action, field){
    if(!action){
      $scope.contact[field].splice($scope.contact[field].indexOf(val), 1);
    }
    else {
      $scope.contact[field].push(val);
    }
    return action;
  };

  if (Discus && Discus.main_doc) {
    $scope.discusData = {};
    $scope.discusData._id = $scope.discus.main_doc._id;
    $scope.discusData.unid = $scope.discus.main_doc.unid;
    $scope.discusData.type = $scope.discus.main_doc.type;
  }

  Discus.clear();
  $scope.discus.current = $scope.contact;
  $scope.isNew = true;

  $rootScope.$watch('contactName', function (v) {
    if (v)
    {
      $scope.contact.Company = v;
    }
  });

  $scope.changeInputOrganization = function(text){
    $scope.inputOrganization = text;
    if(!text){
      $scope.contact.Organization = '';
      $scope.contact.OrganizationID = '';
    }
  };

  $scope.ok = function () {
    if(!security.hasPrivilege('write', $rootScope.user.username)){
      security.addPrivilege('write', 'username', $rootScope.user.username)
    }
    if ($scope.discus.current.isPublic && !security.hasPrivilege('read', 'all')) {
      security.addPrivilege('read', 'role', 'all');
    }
    else if (!$scope.discus.current.isPublic && security.hasPrivilege('read', 'all')) {
      security.removePrivilege('read', 'role', 'all');
    }

    if($scope.inputOrganization && ((typeof $scope.contact.Organization != 'undefined'
        && $scope.contact.Organization[0] != $scope.inputOrganization)
        || typeof $scope.contact.Organization == 'undefined')){
      $scope.modalForm.$setValidity('Organization', false);
    }
    else {
      $scope.modalForm.$setValidity('Organization', true);
    }

    if($filter('emailValidation')($scope.contact.EmailValues)){
      $scope.modalForm.$setValidity('email', true);
    }
    else {
      $scope.modalForm.$setValidity('email', false);
    }

    if($scope.contact.Salary && (typeof Number($scope.contact.Salary) != 'number' || isNaN(Number($scope.contact.Salary)))){
      $scope.modalForm.$setValidity('Salary', false);
    }
    else {
      $scope.modalForm.$setValidity('Salary', true);
    }

    if($scope.contact.Salary && !$scope.contact.currency){
      $scope.modalForm.$setValidity('Currency', false);
    }
    else {
      $scope.modalForm.$setValidity('Currency', true);
    }


    if ($scope.modalForm.$valid) {
      var personCountry = $scope.contact.DocumentType == "Person" && (typeof $scope.contact.Country == 'undefined' || !$scope.contact.Country) &&
      ($scope.contact.ContactStatus.indexOf(14) !== -1 || $scope.contact.ContactStatus.indexOf('14')) !== -1;

      if (!$scope.contact.ContactStatus.length || personCountry) {
        return false;
      }

      Discus.addParticipant($rootScope.user.username,'username', $scope.contact);
      if(Discus.tempParticipants){
        for(var i in Discus.tempParticipants) {
          Discus.addParticipant(Discus.tempParticipants[i], 'username', $scope.contact);
        }
      }
      $rootScope.EmployeeName = $scope.contact.LastName + ' ' + $scope.contact.FirstName + ' ' + $scope.contact.MiddleName;
      if ($scope.linkedTo)
        if (!$scope.discusData || $scope.linkedTo == $scope.discusData.unid) {
          $scope.contact.linkedUNID = $scope.linkedTo;
        } else {
          $scope.contact.linkedUNID = $scope.discusData.unid;
          $scope.contact.SubID = $scope.linkedTo;
        }
      $scope.contact.persist($scope.linkToOrganization?true:false, function () {
        if($scope.linkToOrganization){
          var params = {id: $scope.linkToOrganization.organizationId, "type": "contact"};
          if(is1C){
            params['client'] = '1C';
          }
          $state.go('body.discus', params, {reload: true});
        }
      });
      $rootScope.show.create.form = null;
    }
  };
  $scope.close = function () {
    $scope.inputOrganization = '';
    $scope.linkToOrganization = false;
    $scope.linkedTo = null;
    $rootScope.show.create.form = null;
    if ($scope.discusData && $state.current.name.indexOf('body.index') < 0) {
      $state.go('body.discus', { id: $scope.discusData.unid, type: $scope.discusData.type });
      delete $scope.discusData;
    }
  }
}

function contactNewPersonAutoCtrl($scope, $rootScope, Contact, $http, $state, Dictionary,
        Profile, AutoComplete, MultiselectHelper, Discus, Security) {
  $scope.autoComplete = new AutoComplete();
  $scope.profile = new Profile();
  $scope.busy = true;
  $scope.status = "Подождите...";
  $scope.statusListDict = new Dictionary('StatusList', true, false, true);
  $scope.contact = new Contact({DocumentType: 'Person', 'status': 0, 'Group': [], 'ContactStatus': [], 'AutoDicomposition': true, 'textAuto': ''});
  var security = new Security($scope.contact);
  $scope.busy = false;
  $scope.status = "";
  $scope.discus = Discus;
  $scope.discus.current = $scope.contact;
  $scope.organizationCountries = new Dictionary('Country', true);
  $scope.multiselectHelper = new MultiselectHelper();

  $scope.ok = function () {
    if ($scope.modalForm.$valid) {
      if(!security.hasPrivilege('write', $rootScope.user.username)){
        security.addPrivilege('write', 'username', $rootScope.user.username)
      }
      if(Discus.tempParticipants){
        for(var i in Discus.tempParticipants) {
          Discus.addParticipant(Discus.tempParticipants[i], 'username', $scope.contact);
        }
      }
      if (!$scope.contact.ContactStatus.length || (!$scope.contact.Country && ($scope.contact.ContactStatus.indexOf(14) !== -1 || $scope.contact.ContactStatus.indexOf('14') !== -1))) {
        return false;
      }

      $scope.close();
      $scope.contact.persist();
    }
  }
  $scope.close = function () {
    $rootScope.show.create.form = null;
  }
}

function contactNewOrganizationCtrl($scope, $rootScope, $state, Contact, Dictionary,
        Profile, AutoComplete, MultiselectHelper, Discus, Security, Popup, $filter) {
  $scope.multiselectHelper = new MultiselectHelper();
  $scope.autoComplete = new AutoComplete();
  $scope.profile = new Profile();
  $scope.busy = true;
  $scope.status = "Подождите...";
  $scope.statusListDict = new Dictionary('StatusList', true, false, true);
  $scope.positionsDict = new Dictionary('Positions', true);
  $scope.infoSourceDict = new Dictionary('InformationSourceCatalog', true, false, true);
  $scope.organizationCountries = new Dictionary('Country', true);
  $scope.payMethodList = {
    1:'Оплата всего по готовности',
    2:'Предоплата всего',
    3:'Предоплата только производства'
  };

  $scope.contact = new Contact({
    'DocumentType': 'Organization',
    'status': 0,
    'Group': [],
    'ContactStatus': [],
    'banApi':false,
    'isPublic':true,
    'payMethod':''
  });

  $scope.contact.inHoliday = [''];
  $scope.contact.outHoliday = [''];
  $scope.contact.individuallySamples = [];
  $scope.inHolidayView = [];
  $scope.outHolidayView = [];

  $scope.contact.IsPackageOnly = 1;
  $scope.busy = false;
  $scope.status = "";
  $scope.discus = Discus;

  var security = new Security($scope.contact);
  if(!security.hasPrivilege('write', $rootScope.user.username)){
    security.addPrivilege('write', 'username', $rootScope.user.username)
  }

  if (Discus && Discus.main_doc) {
    $scope.discusData = {};
    $scope.discusData._id = $scope.discus.main_doc._id;
    $scope.discusData.unid = $scope.discus.main_doc.unid;
    $scope.discusData.type = $scope.discus.main_doc.type;
  }

  $scope.changeIndividuallySamples = function(param){
    var indexOf = $scope.contact.individuallySamples.indexOf(param);
    $scope[param] = indexOf;
    if(indexOf > -1){
      $scope.contact.individuallySamples.splice(indexOf, 1);
    }
    else {
      $scope.contact.individuallySamples.push(param);
    }
  };

  $scope.changeHoliday = function(key, index){
    key = key == 'inHoliday'?'inHoliday':'outHoliday';
    var keyView = key == 'inHoliday'?'inHolidayView':'outHolidayView';

    $scope.contact[key][index] = $rootScope.convertObjDateToStr($scope[keyView][index]);
    $scope[keyView][index] = $rootScope.localizeDate($scope[keyView][index]);
  };

  Discus.clear();
  $scope.discus.current = $scope.contact;
  $scope.isNew = true;
  $scope.contact.FormOwnership = '5';

  $rootScope.$watch('EmployeeName', function (v) {
    if (v)
    {
      var old = '';
      if ($scope.contact.Employee) {
        old = $scope.contact.Employee;
        if ($scope.contact.Employee.length) {
          v = ', ' + v;
        }
      }
      $scope.contact.Employee = old + v;
    }
  });

  $scope.ok = function () {
    if($filter('emailValidation')($scope.contact.EmailValues)){
      $scope.modalForm.$setValidity('email', true);
    }
    else {
      $scope.modalForm.$setValidity('email', false);
    }

    if ($scope.modalForm.$valid) {
      if (!$scope.contact.ContactStatus.length) {
        return false;
      }
      if($scope.contact.DocumentType == 'Organization' && typeof $scope.contact.Country == 'undefined'){
        return false;
      }

      message = '';

      $scope.contact.ContactName = $scope.contact.OtherName;

      if (!$scope.contact.ContactName) {
        message += "Вы не ввели официальное название. ";
      }
      if (message.length > 0) {
        new Popup('Discus', message, 'error');
        return false;
      }

      for (var i in $scope.contact.inHoliday) {
        if ((!$scope.contact.inHoliday[i] || !$scope.contact.outHoliday[i]) ||
            ($scope.contact.inHoliday[i].length < 6 || $scope.contact.outHoliday[i].length < 6) ||
            ($scope.contact.inHoliday[i] == 'NaNaNaN' || $scope.contact.outHoliday[i] == 'NaNaNaN')) {
          $scope.contact.inHoliday.splice(i, 1);
          $scope.contact.outHoliday.splice(i, 1);
        }
      }

      Discus.addParticipant($rootScope.user.username,'username', $scope.contact);
      if (Discus.tempParticipants){
        for(var i in Discus.tempParticipants) {
          Discus.addParticipant(Discus.tempParticipants[i], 'username', $scope.contact);
        }
      }

      $rootScope.contactName = $scope.contact.ContactName;
      if ($scope.linkedTo){
        if ($scope.linkedTo == $scope.discusData.unid) {
          $scope.contact.linkedUNID = $scope.linkedTo;
        } else {
          $scope.contact.linkedUNID = $scope.discusData.unid;
          $scope.contact.SubID = $scope.linkedTo;
        }
      }

      if($scope.contact.PayType == '2' && $scope.contact.PayIsDelayPresent == '0' && !$scope.contact.PayPrepayment){
        $scope.contact.PayPrepayment = 100;
      }

      if ($scope.discus.current.isPublic && !security.hasPrivilege('read', 'all')) {
        security.addPrivilege('read', 'role', 'all');
      }
      else if (!$scope.discus.current.isPublic && security.hasPrivilege('read', 'all')) {
        security.removePrivilege('read', 'role', 'all');
      }

      $scope.contact.persist();
      $rootScope.show.create.form = null;
    }
  }
  $scope.close = function () {
    $scope.linkedTo = null;
    $rootScope.show.create.form = null;
    if ($scope.discusData && $state.current.name.indexOf('body.index') < 0) {
      $state.go('body.discus', { id: $scope.discusData.unid, type: $scope.discusData.type });
      delete $scope.discusData;
    }
  }
}
