(function() {
    var lien_fr = jQuery("body #wp-admin-bar-zwt_admin_lang_fr_FR a").attr("href");
   if (lien_fr) jQuery("body #wp-admin-bar-zwt_admin_lang_fr_FR a").attr("href", lien_fr.replace("en_US", "fr_FR").replace("en/", ""));

    var lien_en = jQuery("body #wp-admin-bar-zwt_admin_lang_en_US a").attr("href");
    if (lien_en && lien_en.indexOf("en/" != -1)) lien_en = "/en" + lien_en;
    if (lien_en)jQuery("body #wp-admin-bar-zwt_admin_lang_en_US a").attr("href", lien_en.replace("fr_FR", "en_US"));
	
	if (jQuery("#wp-admin-bar-my-sites-list") && jQuery("#wp-admin-bar-my-sites-list li.menupop").length == 1){
		var li = jQuery("#wp-admin-bar-my-sites-list li.menupop").clone(true);
		li.attr("id", "wp-admin-bar-blog-2");
		
		console.log(li);
		
		jQuery(li).find("a").each(function(){
			jQuery(this).attr("href", jQuery(this).attr("href").replace("aeromorning.com/", "aeromorning.com/en/"));
		});
		
		li.find("a.ab-item:first").html(li.find("a.ab-item:first").html().replace("AeroMorning.com", "AeroMorning.com En"));
		
		jQuery("#wp-admin-bar-my-sites-list").append(li);
		
	}
	$('#job_schedule_listing').attr('required');

})();;
;