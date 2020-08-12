/*! xxx
 * xuServer::xsClass.js v4.2 (http://xuserver.net || https://fr.linkedin.com/pub/gael-jaunin/40/107/9a0)
 * Copyright (c) 2011-2015 Gael JAUNIN.
 * Licensed under MIT
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */



var $myScreen = "";
var xsNotifications;
function xsLayout(){
    xsNotifications = $("<div style='z-index:999; position: fixed; right:0; padding-top:5px; width:300px; min-height:1px;' />")
    $("body").prepend(xsNotifications);
    
}
$( document ).ready(function() {
	xsLayout();
    duplicateIDs($("body"))
    ajaxResponse($("body"));
	$myScreen = $("#myScreen")
});


function duplicateIDs($html){
    console.groupCollapsed("duplicate ID")    
    $html.find('[id]').each(function(){
        var ids = $('[id="'+this.id+'"]');
        
        if(ids.length>1 && ids[0]==this){
            var idFound = this.id
            console.groupCollapsed("ID #"+idFound)
            var placeholder=null;
            ids.each(function(){
                var current = $(this)
                if(current.hasClass("xs-placeholder")){
                    console.log("found placeholder")
                    placeholder=current
                }
            });
            if(placeholder==null){
                placeholder=$(ids[0]).addClass("xs-placeholder")
                console.log("create placeholder")
            }
            ids.each(function(){
                var current = $(this)
                if(current.hasClass("xs-placeholder")){
                }else{
                    current.attr("id","");
                    if(current.html().trim()==""){
                        console.log(" remove empty element ")
                        current.remove()
                    }else if(current.html().trim()==placeholder.html().trim()){
                        console.log(" remove indentical element ")
                        current.remove()
                    }else{
                        console.log(" append element children")
                        placeholder.append(current.children());                        
                    }
                }
            });
            console.groupEnd()
        }
        
    });
    console.groupEnd()
} 





function ajaxResponse(html){
	$html = $(html);
	var time = new Date().toLocaleString();
    console.groupCollapsed("ajaxResponse "+time)
    
	if($html.prop("tagName")=="BODY"){
	}else{
		$html=$("<div></div>").append($html)
	}
	
    console.groupCollapsed("xs class")
    console.log(".xs-link")
    $html.find("a.xs-link").click(function(e) {
		var fd = new FormData();
		fd.append("method","form");
		fd.append("model_build",$(this).attr("href"));
		fdPost(fd)
		e.preventDefault();
	});
    
    console.log(".xs-notify")
    $html.find(".xs-notify").each(function() {
        var $notification = $(this);
        xsNotifications.prepend($notification);
        setTimeout(function(){ $notification.fadeOut().remove() }, 5000);
    });
    console.log(".xs-debug")
    $html.find(".xs-debug").each(function() {
        var $notification = $(this);
        $myScreen.prepend($notification.addClass("sticky-top"));
        setTimeout(function(){ $notification.fadeOut().remove() }, 5000);
    });
    console.groupEnd()

    console.groupCollapsed("dispatch ID")    
	$html.find("form").each(function() {
		$form = $(this)
		$ajaxForm($form);
		$ajaxResponseDispatchNode($form)
	});
	
	$html.find("div[id]").each(function() {
    	var $element = $(this);
        $ajaxResponseDispatchNode($element)
	});
    console.groupEnd()
    
    
    if($html.prop("tagName")=="BODY"){
        
	}else{
        $myScreen.append($html);
        //console.log($html.html().length)
	}
    console.groupEnd()
    
    
    
    

}



function $ajaxForm($form){
	
	$form.find("input[type=submit]").click(function(){
		$button = $(this)
		$form.find("input[name=method").val($button.attr('value'))
		$form.xsMethod = $button.attr('value');
		//$form.submit();
	})
	
	
	$form.submit(function(e){
		 //var $form = $(this);
		 
		 var fd = new FormData(this);
         $form.find('input[type="checkbox"]').each(function(e){
			 if(! $(this).prop('checked')){
				 fd.append($(this).attr('name'),"0")
			 }
		 });
		 fdPost(fd)
		 
		e.preventDefault();
	 });
}


function fdPost(fd){
	$.ajax({
		 type: "post",
		 data: fd,
		 url: "/xs-framework/v5/router.php",
		 processData: false,
		 contentType: false,
		 cache: false,
		 success: function(data) {
			 
			 ajaxResponse(data);
		 },
		 complete: function() {
		 }
	 });
}

function $ajaxResponseDispatchNode(element){
	var $body=$("body");
	var selector = element.prop("tagName")+"[id='"+element.attr("id")+"']";
	if(element.attr("id")==""){
        return false;
	}else{
    	var existing = $body.find(selector);
    	if(existing==undefined){
    		return false;
    	}
    	if(existing.length != 0 ){
    		console.log("#"+ element.attr("id"))
    		existing.replaceWith(element);
            return true;
    	}else{
    		console.warn("#"+ element.attr("id"))
    		$myScreen.append(element)
    		return false;
    	}
	}
}




