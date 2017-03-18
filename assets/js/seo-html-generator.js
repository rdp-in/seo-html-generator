function init() {
	jQuery(".seo-btn").click(generateHtml);
}

function generateHtml(e)
{
	e.preventDefault();
	var page_id = jQuery(this).attr("id");
	jQuery.ajax({
		type: 'post',
		url: ajax_data.ajax_url,
		data: {
			action : "seoGenerateHtml",
			"pageId" : page_id
		},
		success: function(response) {
			// console.log(response);
			var responseData = JSON.parse(response);
			if (responseData.result == "failure") {
				showError();
			} else if (responseData.result == "success") {
				renderHtml(responseData.data);
			}
		}
	});
}

function showError()
{
	alert("An error occurred. Please try again.");
}

function renderHtml(html)
{
	if (jQuery(".seo-html-container").length == 0) {
		var container = "<div class='seo-html-container'><textarea rows='10' cols='80' onclick='this.select()'>"+html+"</textarea></div>"
		jQuery(container).insertAfter(".wrap");
	} else {
		jQuery(".seo-html-container textarea").val(html);
	}

    jQuery('html, body').animate({
        scrollTop: jQuery(".seo-html-container").offset().top
    }, 2000);
}

jQuery(document).ready(init);