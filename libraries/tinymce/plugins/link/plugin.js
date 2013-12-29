tinymce.PluginManager.add("link",function(e){function t(t){return function(){var n=e.settings.link_list;"string"==typeof n?tinymce.util.XHR.send({url:n,success:function(e){t(tinymce.util.JSON.parse(e))}}):t(n)}}function n(t){function n(e){var t=d.find("#text");(!t.value()||e.lastControl&&t.value()==e.lastControl.text())&&t.value(e.control.text()),d.find("#href").value(e.control.value())}function i(){var e=[{text:"None",value:""}];return tinymce.each(t,function(t){e.push({text:t.text||t.title,value:t.value||t.url,menu:t.menu})}),e}function a(t){var n=[{text:"None",value:""}];return tinymce.each(e.settings.rel_list,function(e){n.push({text:e.text||e.title,value:e.value,selected:t===e.value})}),n}function o(t){var n=[{text:"None",value:""}];return e.settings.target_list||n.push({text:"New window",value:"_blank"}),tinymce.each(e.settings.target_list,function(e){n.push({text:e.text||e.title,value:e.value,selected:t===e.value})}),n}function r(t){var i=[];return tinymce.each(e.dom.select("a:not([href])"),function(e){var n=e.name||e.id;n&&i.push({text:n,value:"#"+n,selected:-1!=t.indexOf("#"+n)})}),i.length?(i.unshift({text:"None",value:""}),{name:"anchor",type:"listbox",label:"Anchors",values:i,onselect:n}):void 0}function s(){c||0!==g.text.length||this.parent().parent().find("#text")[0].value(this.value())}var l,u,c,d,m,f,h,g={},p=e.selection,v=e.dom;l=p.getNode(),u=v.getParent(l,"a[href]"),g.text=c=u?u.innerText||u.textContent:p.getContent({format:"text"}),g.href=u?v.getAttrib(u,"href"):"",g.target=u?v.getAttrib(u,"target"):"",g.rel=u?v.getAttrib(u,"rel"):"","IMG"==l.nodeName&&(g.text=c=" "),t&&(m={type:"listbox",label:"Link list",values:i(),onselect:n}),e.settings.target_list!==!1&&(h={name:"target",type:"listbox",label:"Target",values:o(g.target)}),e.settings.rel_list&&(f={name:"rel",type:"listbox",label:"Rel",values:a(g.rel)}),d=e.windowManager.open({title:"Insert link",data:g,body:[{name:"href",type:"filepicker",filetype:"file",size:40,autofocus:!0,label:"Url",onchange:s,onkeyup:s},{name:"text",type:"textbox",size:40,label:"Text to display",onchange:function(){g.text=this.value()}},r(g.href),m,f,h],onSubmit:function(t){function n(t,n){window.setTimeout(function(){e.windowManager.confirm(t,n)},0)}function i(){a.text!=c?u?(e.focus(),u.innerHTML=a.text,v.setAttribs(u,{href:o,target:a.target?a.target:null,rel:a.rel?a.rel:null}),p.select(u)):e.insertContent(v.createHTML("a",{href:o,target:a.target?a.target:null,rel:a.rel?a.rel:null},a.text)):e.execCommand("mceInsertLink",!1,{href:o,target:a.target,rel:a.rel?a.rel:null})}var a=t.data,o=a.href;return o?o.indexOf("@")>0&&-1==o.indexOf("//")&&-1==o.indexOf("mailto:")?(n("The URL you entered seems to be an email address. Do you want to add the required mailto: prefix?",function(e){e&&(o="mailto:"+o),i()}),void 0):/^\s*www\./i.test(o)?(n("The URL you entered seems to be an external link. Do you want to add the required http:// prefix?",function(e){e&&(o="http://"+o),i()}),void 0):(i(),void 0):(e.execCommand("unlink"),void 0)}})}e.addButton("link",{icon:"link",tooltip:"Insert/edit link",shortcut:"Ctrl+K",onclick:t(n),stateSelector:"a[href]"}),e.addButton("unlink",{icon:"unlink",tooltip:"Remove link",cmd:"unlink",stateSelector:"a[href]"}),e.addShortcut("Ctrl+K","",t(n)),this.showDialog=n,e.addMenuItem("link",{icon:"link",text:"Insert link",shortcut:"Ctrl+K",onclick:t(n),stateSelector:"a[href]",context:"insert",prependToContext:!0})});