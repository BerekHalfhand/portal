$('.groups i.icon').on('click', function (e) {
	$(this).toggleClass('icon-plus icon-minus');

	if (groups.indexOf($(this).attr('href')) == -1) {
		groups.push($(this).attr('href'));	
	} else {
		var index = groups.indexOf($(this).attr('href'));
		groups.splice(index, 1);
	};
	localStorage['group-collapsed'] = groups.join();	
});

$( document ).ready(function() {
	if (! localStorage['group-collapsed']){
			groups = [];
		} else {
			groups = localStorage['group-collapsed'].split(',');
	}
	for(var gr in groups){
		$("i[href='" + groups[gr] +"']").toggleClass('icon-plus icon-minus');
		$(groups[gr]).addClass('in');
	}
});

$('#button-bank').on('click', function (e) {
  $('#panel-bank').removeClass('hide');
  $('#panel-contacts').addClass('hide');
});
$('#button-contacts').on('click', function (e) {
	$('#panel-contacts').removeClass('hide');
  $('#panel-bank').addClass('hide');
});

$('.for-collapse').on('click', function (e) {
  $($(this).attr('href')).collapse('toggle');
});

$('#menu-contact a').on('click', function (e) {
  $('#menu-contact a').removeClass('active');
  $('div.radio-panel').addClass('hide');
  $(this).addClass("active");
  $($(this).attr('data-href')).removeClass('hide');
});

$('#contact-edit').on('click', function (e) {
  $('input.editable, textarea.editable, select.editable').attr('disabled',false);
  $('a.editable').removeClass('hide');
});

$('.toggle-sale').on('click', function(e){
	$($(this).attr('data-href')).toggleClass("hide");
})
