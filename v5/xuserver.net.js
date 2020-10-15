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


function loadScript(src) {
	  return new Promise(function (resolve, reject) {
	    if ($("script[src='" + src + "']").length === 0) {
	        var script = document.createElement('script');
	        script.onload = function () {
	            resolve();
	        };
	        script.onerror = function () {
	            reject();
	        };
	        script.src = src;
	        document.head.appendChild(script);
	    } else {
	        resolve();
	    }
	});
}


var xsRouter = "/xs-framework/v5/router.php";


var $myScreen = "";
var xsNotifications;
function xsLayout(){
    xsNotifications = $("<div style='z-index:999; position: fixed; right:0; padding-top:5px; width:300px; min-height:1px;' />")
    $("body").prepend(xsNotifications);
    
}

$( document ).ready(function() {
	xsLayout();
	$myScreen = $("#xs-screen")
	if($myScreen.length != 1){
		$myScreen = $("body")
	}else{
	}
	loadScript("https://cdn.jsdelivr.net/gh/xcash/bootstrap-autocomplete@v2.3.7/dist/latest/bootstrap-autocomplete.min.js").then(function(){
		duplicateIDs($("body"))
	    ajaxResponse($("body"));
	});
	
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
    	var $link = $(this);
    	var fd = new FormData();
    	fd.append("model_build",$link.attr("href"));
    	fd.append("model_method",$link.attr("method"));
		fdPost(fd)
		e.preventDefault();
	});
    
    console.log("button.close")
    $html.find("button.close").click(function(e) {
    	var $link = $(this);
    	$link.parent().remove()
	});
    
    $html.find("table").each(function() {
    	var $table = $(this);
		if(! $table.hasClass("xs-table")){
			$table.addClass("xs-table").find("a.xs-action").each(function(){
				var $link = $(this);
				$link.click(function(e){
					var fd = new FormData();
					fd.append("model_build",$link.attr("href"));
					fd.append("model_method",$link.attr("method"));
					$table.find("input.xs-action").each(function(e){
						 if($(this).prop('checked')){
							 fd.append($(this).attr('name'),$(this).attr('value'))
						 }
					 });
					fdPost(fd)
					e.preventDefault();
				});
			});
		}
	});
     
    console.log(".xs-notify")
    if($html.find(".xs-notify.clear").length != 0 ){
    	xsNotifications.find(".xs-notify").remove();
    }
    $html.find(".xs-notify").each(function() {
        var $notification = $(this).hide();
        xsNotifications.prepend($notification.fadeIn());
        setTimeout(function(){ $notification.fadeOut(400, function(){$notification.remove();}) }, 5000);
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
    
	$html.find("div[id], span[id],a.xs-link[id]").each(function() {
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
		$form.find("input[name=model_method").val($button.attr('method'))
	});
	$form.find("input.xs-type-fk").each(function(){
		var input = $(this).hide();
		
		var dual = input.prev();
		dual.click(function () {
			var self = $(this);
			self.select();
		}).blur(function () {
			var self = $(this);
			if(self.val()==""){
				self.val(self.attr("placeholder"))
			}
		});
		dual.autoComplete({
			minLength:2,
			preventEnter:true,
		    resolverSettings: {
		        url: xsRouter+"?autoComplete="+input.attr("type"),
		        fail:function(e){
		        }
		    }
		}).on('autocomplete.select', function (evt, item) {
			input.val(item.value);
			dual.attr("placeholder",item.text);
			dual.val(item.text);
		}).on('autocomplete.freevalue', function (evt, item) {
			dual.val(dual.attr("placeholder"))
		});
		
	});
	
	$form.find("input.xs-type-file").each(function(){
		var input = $(this);
		$ajaxFormFile(input);
	});
	
	$form.submit(function(e){
		 var fd = new FormData(this);
         $form.find('input[type="checkbox"]').each(function(e){
			 if(! $(this).prop('checked')){
				 fd.append($(this).attr('name'),"0")
			 }
		 });
		 fdPost(fd);
		 e.preventDefault();
	 });
}


function $ajaxFormFile(input) {
	
	var btnSelectFile =  $("<input type='button' class='btn btn-primary xs-fn-filedropper' value='Select file' /> ").click(function(){
		var me = $(this);
		input.click()
	});
	
	var helper = $("<span class='col-sm-12 form-text text-muted small fileHelper'>choose file ...</span>")
	var thumb = input.prev();
	
	input.change(function(){
		var me = $(this);
		helper.html(me[0].value.split("\\").pop())
	});
	function randomtag() {
	    return Math.round(+new Date() / 1000);
	}
	var btnViewFile =  $("<input type='button' class='btn btn-outline-primary' value='open' />").click(function(){
		var me = $(this);
		var body = $("body");
		var frame = $ ("<div><iframe src='"+xsRouter+"?file="+input.attr("title")+"' style='border:0;width:100%;min-height:80vh;'/></div>").appendTo(body)
		var bs = bsModal(frame);
		bs.dialog.addClass("mw-100 w-75");
	});
	
	if(input.attr("title")!=""){
		input.hide().parent().append(btnSelectFile, btnViewFile,thumb).append(helper)
		//helper.html(input.attr("title").split("/").pop());
		helper.html("drag new file or click view button to open existing file ")
	}else{
		input.hide().parent().append(btnSelectFile, btnViewFile.attr("disabled","disabled").addClass("disabled"),thumb ).append(helper)
	}
	var xhr = new XMLHttpRequest();
	if (xhr.upload) {
		var filedrag = btnSelectFile;
		$("html").on("dragover", function(e) {
            e.preventDefault(); e.stopPropagation();
            filedrag.removeClass('btn-primary').addClass('btn-success');
            btnSelectFile.val("Drag here");
        });
		$("html").on("drop", function(e) {
			e.preventDefault(); e.stopPropagation();
			$("input.xs-fn-filedropper").val("Select file").removeClass('btn-success').addClass('btn-primary');
			
		});
		
	    // Drag over
		filedrag.on('dragover', function (e) {
	        e.stopPropagation(); e.preventDefault();
	        filedrag.removeClass('btn-success').addClass('btn-warning');
	        btnSelectFile.val("Drop here");
	    });
		filedrag.bind('dragleave', function(e){
			e.stopPropagation();
	        e.preventDefault();
	        filedrag.removeClass('btn-warning').addClass('btn-success');
		});
		// Drop
		filedrag.on('drop', function(e) {
			e.stopPropagation(); e.preventDefault();
			$("input.xs-fn-filedropper").val("select file").removeClass('btn-warning').removeClass('btn-success').addClass('btn-primary');
			//btnSelectFile.val("Dropped");
			var file = e.originalEvent.dataTransfer.files;
			input.prop('files', e.originalEvent.dataTransfer.files);
			input.change();
			
		});
	
	}
}



function fdPost(fd){
	$.ajax({
		 type: "post",
		 data: fd,
		 url: xsRouter,
		 processData: false,
		 contentType: false,
		 cache: false,
		 beforeSend: function() {
			 //for (var value of fd.values()) {console.log(value);}
		 },
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
		console.log("empty id "+element.prop("tagName")+"#"+ element.attr("id"))
        return false;
	}else{
		
		var existing = $body.find(selector);
    	if(existing==undefined){
    		return false;
    	}
    	if(existing.length != 0 ){
    		console.log("replace "+element.prop("tagName")+"#"+ element.attr("id"))
    		existing.replaceWith(element);
            return true;
    	}else{
    		console.log("append "+element.prop("tagName")+"#"+ element.attr("id"))
    		$myScreen.append(element)
    		//bsModal(element)
    		return false;
    	}
	}
}


var bsZindex=1040;
function bsModal(content, options) {
    var defaults = {
        title: "dialog modal box",
        size:"modal-lg",
        print:false,
        animation:"fade"
    }
    var content = $(content)
    options = jQuery.extend(defaults, options);
  	var bs = $('<div class="modal '+options.animation+' bsModal " id="basicModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true" />');
    bs.dialog=$("<div class='modal-dialog "+options.size+"  ' />")
  	bs.container = $('<div class="modal-content container">');
  	bs.body = $('<div class="modal-body">').html(content);
  	
  	bs.dialog.append(bs.container);
  	bs.container.append(bs.body);
  	bs.append(bs.dialog);
  	$("body").prepend(bs);
  	
  	bs.modal('show');
    bsZindex++;
    bs.css("z-index", bsZindex);
    /*
    bs.on('hidden.bs.modal', function (e) {
    	console.log("remove modal");
    	$(this).data('bs.modal', null).remove();
    	
    })
    */
    return bs;
}


