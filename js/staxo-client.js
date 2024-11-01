var tax_cntl=[];function get_cntl(a){for(const b of tax_cntl)if(b[0]===a)return b}function add_nt_element(a,b){if(a.classList.contains("NoTerm"))return!0;let c=a.getElementsByTagName("li"),d=c[0].cloneNode(!0),e=d.getAttribute("id"),f=d.getElementsByTagName("input")[0];if(void 0===f)return!1;let g=d.getElementsByTagName("ul");for(let c of g)d.removeChild(c);let h=f.value;d.id=e.replace("-"+h,"--1"),f.id=f.id.replace("-"+h,"--1"),f.checked=!b,f.value=-1;let i=d.getElementsByTagName("label")[0],j=i.lastChild.data.replace(/[\n\r\t]/g,"");return i.lastChild.data=i.lastChild.data.replace(j," No Term"),c[0].parentNode.insertBefore(d,c[0]),a.classList.add("NoTerm"),!0}function process_no_term(a,b){let c=document.getElementById(a);return null!==c&&add_nt_element(c,b)}function add_no_term(a){if(cntl=get_cntl(a),0===cntl[2]&&0!==cntl[6]){terms_count=!1;let b=document.getElementsByName("tax_input["+a+"][]");for(let a of b)a.checked&&(terms_count=!0);process_no_term(a+"-pop",terms_count),process_no_term(a+"-all",terms_count),process_no_term("taxonomy-"+a,terms_count)}}function chk_radio_client(a){let b=document.getElementById("taxonomy-"+a),c=b.getElementsByTagName("input"),d=!1;for(let b of c)"checkbox"===b.type&&(b.type="radio",b.setAttribute("role","radio"),b.addEventListener("click",()=>{adj_radio_client(a,b.value)}),b.addEventListener("keypress",()=>{adj_radio_client(a,b.value)}),d=!0);d&&hier_cntl_check(a)}function adj_radio_client(a,b){let c=document.getElementById("taxonomy-"+a),d=c.getElementsByTagName("input");for(let c in d)d[c].checked=!1,d[c].value===b&&(d[c].checked=!0)}function dom_radio_client(a){add_no_term(a),chk_radio_client(a);let b=document.getElementById(a+"-pop");b.setAttribute("role","radiogroup");let c=document.getElementById(a+"-all");c.setAttribute("role","radiogroup");let d=document.getElementById(a+"-add-submit");d.addEventListener("click",()=>{adj_radio_client(a,-1)}),d.addEventListener("keypress",()=>{adj_radio_client(a,-1)});const e=document.getElementById(a+"-all"),f=new MutationObserver(function(){chk_radio_client(a)});f.observe(e,{childList:!0,subtree:!0})}function qe_error_set_msg(a,b,c){let d=a.getElementsByClassName("notice-error");if(d){for(let e of d)if(e.classList.contains(b+"-err")){e.classList.contains("hidden")&&e.classList.remove("hidden");let b=e.getElementsByTagName("p");return b[0].innerHTML=c,void a.getElementsByClassName("save")[0].setAttribute("disabled","disabled")}let e=document.createElement("div");e.classList.add("notice","notice-error","notice-alt","inline","staxo",b+"-err");let f=document.createElement("p");f.classList.add("error"),f.innerHTML=c,e.appendChild(f);let g=a.getElementsByClassName("submit");g[0].appendChild(e),a.getElementsByClassName("save")[0].setAttribute("disabled","disabled")}}function qe_error_clear_msg(a,b){let c=a.getElementsByClassName("notice-error");if(c){all_hidden=!0;for(let a of c)if(a.classList.contains(b+"-err")){a.classList.contains("hidden")||a.classList.add("hidden");let b=a.getElementsByTagName("p");b[0].innerHTML=""}else a.classList.contains("staxo")&&!a.classList.contains("hidden")&&(all_hidden=!1);all_hidden&&a.getElementsByClassName("save")[0].removeAttribute("disabled")}}function chk_qe_radio_client(a){let b=event.target.tagName.toLowerCase(),c=event.target.classList;if(("button"!==b||c.contains("editinline")||c.contains("save"))&&("option"!==b||"_status"===event.target.parentNode.name)&&("input"!==b||event.target.name==="tax_input["+a+"][]")){let c=document.getElementsByClassName("inline-edit-row");for(let e of c){if("edit-"!==e.id.substring(0,5))continue;let c=document.getElementById("post-"+e.id.substring(5)),f=c.getElementsByClassName("taxonomy-"+a),g=f[0].getElementsByTagName("a").length,h=get_cntl(a),i=e.getElementsByClassName(a+"-checklist");2>g&&i[0].setAttribute("role","radiogroup"),0===h[2]&&add_nt_element(i[0],0<g);let j=i[0].getElementsByTagName("input"),k=0;for(let a of j)a.checked&&k++,2>g&&"checkbox"===a.type&&(a.type="radio",a.setAttribute("role","radio"));var d;if("option"===b)d=event.target.value,qe_error_clear_msg(e,a);else{const a=e.getElementsByClassName("inline-edit-status")[0];d=a.getElementsByTagName("select")[0].value}if("new"===d||"auto-draft"===d||"trash"===d)return;if(1===h[1]&&"publish"!==d&&"future"!==d)return;if(1<k)qe_error_set_msg(e,a,h[5]);else if(0===k)qe_error_set_msg(e,a,h[3]);else return void qe_error_clear_msg(e,a)}}}function rst_qe_radio_client(a){if("button"===event.target.tagName.toLowerCase()){let b=document.getElementsByClassName("inline-edit-row");for(let c of b){if("edit-"!==c.id.substring(0,5))continue;chk_qe_radio_client(a);let b=c.getElementsByClassName(a+"-checklist");b[0].removeAttribute("role");let d=b[0].getElementsByTagName("input");for(let a of d)"radio"===a.type&&(a.type="checkbox",a.removeAttribute("role"))}}}function dom_qe_radio_client(a){const b=document.getElementById("the-list");b.parentElement.addEventListener("click",()=>{chk_qe_radio_client(a)}),b.parentElement.addEventListener("keypress",()=>{chk_qe_radio_client(a)});const c=b.getElementsByTagName("tr");for(let b of c){let c=b.id.match(/[0-9]+$/g)[0];b.addEventListener("click",()=>{rst_qe_radio_client(a,c)})}}function hier_tax_count(a){let b,c,d=document.getElementById("taxonomy-"+a),e=d.getElementsByTagName("input"),f=[];for(b in e)e[b].checked&&(c=e[b].value,0<c&&!f.includes(c)&&f.splice(0,0,c));return f.length}function hier_cntl_check(a,b=!1){const c=get_cntl(a),d=document.getElementById("post_status").value;if("new"===d||"auto-draft"===d||"trash"===d)return;if(1===c[1]&&"publish"!==d&&"future"!==d)return;let e=hier_tax_count(a),f=!1;null!==c[2]&&e<c[2]&&(set_errblock(a,c[3]),f=!0),null!==c[4]&&e>c[4]&&(set_errblock(a,c[5]),f=!0),f||clear_errblock(a),f&&b&&(event.stopPropagation(),event.preventDefault())}function dom_hier_cntl_check(a){let b=document.getElementById("taxonomy-"+a),c=b.getElementsByTagName("input");for(let b of c)b.addEventListener("click",()=>{hier_cntl_check(a)}),b.addEventListener("blur",()=>{hier_cntl_check(a)});document.getElementById("publish").addEventListener("click",()=>{hier_cntl_check(a,!0)});var d=document.getElementById("save-post");d&&(d.addEventListener("click",()=>{hier_cntl_check(a,!0)}),d.addEventListener("keypress",()=>{hier_cntl_check(a,!0)}))}function tag_tax_count(a){const b=document.getElementById(a),c=b.getElementsByTagName("ul")[0];return c.getElementsByTagName("li").length}function tag_cntl_check(a,b=!1){var c=document.getElementById("post_status").value;if("new"===c||"auto-draft"===c||"trash"===c)return;const d=get_cntl(a);if(1===d[1]&&"publish"!==c&&"future"!==c)return;document.getElementById("new-tag-"+a).removeAttribute("readonly"),document.getElementById("link-"+a).removeAttribute("disabled");let e=document.getElementById("tagcloud-"+a);null!==e&&e.removeAttribute("disabled");let f=tag_tax_count(a),g=!1;null!==d[2]&&f<d[2]&&(set_errblock(a,d[3]),g=!0),null!==d[4]&&(f>=d[4]&&(document.getElementById("new-tag-"+a).setAttribute("readonly","readonly"),document.getElementById("link-"+a).setAttribute("disabled","disabled"),null!==e&&e.setAttribute("disabled","disabled")),f>d[4]&&(set_errblock(a,d[5]),g=!0)),g||clear_errblock(a);1===d[4]&&0<=f&&2>=f&&!b||g&&(event.stopPropagation(),event.preventDefault())}function dom_tag_cntl_check(a){let b=document.getElementById("publish");null!==b&&b.addEventListener("click",()=>{tag_cntl_check(a,!0)}),b=document.getElementById("save-post"),null!==b&&(b.addEventListener("click",()=>{tag_cntl_check(a,!0)}),b.addEventListener("keypress",()=>{tag_cntl_check(a,!0)}));let c=document.getElementById(a);const d=c.getElementsByTagName("ul")[0],e=new MutationObserver(function(){tag_cntl_check(a)});e.observe(d,{childList:!0,subtree:!0})}let block_limit=function(a,b){function c(a){if("button"===a.target.tagName.toLowerCase()){var b=a.target.className,c=b.includes("editor-post-publish-button__button");c&&(k||j)&&(alert("Cannot Publish due to taxonomy restrictions"),a.stopPropagation(),a.preventDefault(),a.target.disabled=!0)}}function d(a,b){let c=1===i[1]?["publish","future"].includes(a):!["new","auto-draft","trash"].includes(a),d=["publish","future"].includes(a),e="";if(null!==i[2]&&b<i[2]&&(e=i[3]),null!==i[4]&&b>i[4]&&(e=i[5]),c&&e){var g=document.getElementsByClassName("editor-post-save-draft");if(0<g.length&&(g[0].disabled=!0),g=document.getElementsByClassName("editor-post-switch-to-draft"),0<g.length&&(g[0].disabled=!0),f("core/notices").createNotice("error",e,{id:"str_notice_"+h,isDismissible:!1}),d||j||(j=!0,f("core/editor").lockPostSaving("str_"+h+"_lock")),!k){k=!0;var l=document.getElementsByClassName("editor-post-publish-button__button");0<l.length&&(l[0].disabled=!0),f("core/edit-post").disablePublishSidebar,f("core/editor").isPublishable=!1}}else{var g=document.getElementsByClassName("editor-post-save-draft");if(0<g.length&&(g[0].disabled=!1),g=document.getElementsByClassName("editor-post-switch-to-draft"),0<g.length&&(g[0].disabled=!1),f("core/notices").removeNotice("str_notice_"+h),j&&(j=!1,f("core/editor").unlockPostSaving("str_"+h+"_lock")),k){k=!1;var l=document.getElementsByClassName("editor-post-publish-button__button");0<l.length&&(l[0].disabled=!1),f("core/edit-post").enablePublishSidebar}}}const{select:e,dispatch:f,subscribe:g}=a.data,h=b,i=get_cntl(h);console.log(i);let j=!1,k=!1;const l=()=>e("core/editor").getEditedPostAttribute("status"),m=()=>e("core/editor").getEditedPostAttribute(h);var n=document.getElementById("editor");n.addEventListener("click",a=>{c(a)},!0);let o=i[6],p=i[2];d(o,p),g(()=>{const a=l(),b=a!==o;o=a;const c=m();if(void 0!==c){const a=c.length!==p;p=c.length,(a||b)&&d(o,p)}})};function clear_errblock(a){let b=document.getElementById("err-"+a);null===b||b.classList.contains("hidden")||(b.classList.add("hidden"),b.getElementsByTagName("p")[0].innerHTML="")}function set_errblock(a,b){let c=document.getElementById("err-"+a);null!==c&&(c.classList.contains("hidden")&&c.classList.remove("hidden"),c.getElementsByTagName("p")[0].innerHTML=b)}function proc_qe_tax_cntl(a,b){let c=event.target.tagName.toLowerCase(),d=event.target.classList;if(("button"!==c||d.contains("editinline")||d.contains("save"))&&("option"!==c||"_status"===event.target.parentNode.name)&&("input"!==c||event.target.name==="tax_input["+a+"][]")&&("textarea"!==c||d.contains("tax_input_"+a))){let d=document.getElementsByClassName("inline-edit-row");for(let f of d){if("edit-"!==f.id.substring(0,5))continue;const d=get_cntl(a);var e;if("option"===c)e=event.target.value,qe_error_clear_msg(f,a);else{const a=f.getElementsByClassName("inline-edit-status")[0];e=a.getElementsByTagName("select")[0].value}if("new"===e||"auto-draft"===e||"trash"===e)return;if(1===d[1]&&"publish"!==e&&"future"!==e)return;let g=0;if(b){let b=f.getElementsByClassName(a+"-checklist"),c=b[0].getElementsByTagName("input");for(let a of c)a.checked&&g++}else{let b=f.getElementsByClassName("tax_input_"+a),c=b[0].value;g=0===c.length?0:(c.match(/,/g)||[]).length+1}let h=!1;null!==d[2]&&g<d[2]&&(qe_error_set_msg(f,a,d[3]),h=!0),null!==d[4]&&g>d[4]&&(qe_error_set_msg(f,a,d[5]),h=!0),h||qe_error_clear_msg(f,a)}}}function dom_qe_cntl_check(a,b){const c=document.getElementById("the-list");c.parentElement.addEventListener("click",()=>{proc_qe_tax_cntl(a,b)}),c.parentElement.addEventListener("focusout",()=>{proc_qe_tax_cntl(a,b)})}
