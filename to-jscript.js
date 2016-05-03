$(document).ready(function() {
 var respMenuShown = false;
 $("#menu-btn").on('click', function() {
  $("#menubar").slideToggle('fast');
	respMenuShown = !(respMenuShown);
 });
 $(window).on('resize', function() {
  if ($(this).width() > 566 || respMenuShown == true) { 
   $("#menubar").show();
	} else {
   $("#menubar").hide();
	}
 });
});
