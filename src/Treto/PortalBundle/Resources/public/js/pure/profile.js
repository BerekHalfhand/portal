$('.groups i.icon').on('click', function (e) {
	$(this).toggleClass('icon-arrow-right icon-arrow-down');

	if (groups.indexOf($(this).attr('href')) == -1) {
		groups.push($(this).attr('href'));	
	} else {
		var index = groups.indexOf($(this).attr('href'));
		groups.splice(index, 1);
	};
	localStorage['group-collapsed'] = groups.join();	
});