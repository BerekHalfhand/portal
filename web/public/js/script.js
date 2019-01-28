$.each($('div.color-panel'), function(ind, value){
  value.style.borderTopColor = "#" + Math.floor(Math.random()*16777215).toString(16);
});


