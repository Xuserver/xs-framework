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




$( document ).ready(function() {
	ajaxResponse(this);
});

function ajaxResponse(html){
	$html = $(html);
	
	$html.find("form").each(function() {
		$form = $(this)
		$ajaxForm($form);
		$ajaxResponseDispatchNode($form)
	});
	
	$html.find("div[id]").each(function() {
    	var element = $(this);
    	$ajaxResponseDispatchNode(element)
	});
}

function $ajaxForm(form){
	form.submit(function(e){
		 var $form = $(this);
		 var fd = new FormData(this);
		 $form.find('input[type="checkbox"]').each(function(e){
			 if(! $(this).prop('checked')){
				 fd.append($(this).attr('name'),"0")
			 }
		 });
		 $.ajax({
			 data: fd,
			 url: "index.php",
			 processData: false,
			 contentType: false,
			 cache: false,
			 success: function(data) {
				 ajaxResponse(data);
			 },
			 complete: function() {
			 }
		 });
		e.preventDefault();
	 });
}

function $ajaxResponseDispatchNode(element){
	var $body=$("body");
	
	var selector = element.prop("tagName")+"[id='"+element.attr("id")+"']";
	
	if(element.attr("id")==""){
		alert("1")
        return false;
	}else{
    	var existing = $body.find(selector);
    	if(existing==undefined){
    		alert("2")
    		return false;
    	}
    	if(existing.length != 0 ){

        	existing.replaceWith(element.show());
            return true;
    	}else{
    		alert("3")
    		return false;
    		
    	}
	}
}




