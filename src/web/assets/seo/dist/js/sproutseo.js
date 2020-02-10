/*! For license information please see sproutseo.js.LICENSE */
!function(e){var t={};function i(n){if(t[n])return t[n].exports;var a=t[n]={i:n,l:!1,exports:{}};return e[n].call(a.exports,a,a.exports,i),a.l=!0,a.exports}i.m=e,i.c=t,i.d=function(e,t,n){i.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},i.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},i.t=function(e,t){if(1&t&&(e=i(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(i.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var a in e)i.d(n,a,function(t){return e[t]}.bind(null,a));return n},i.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return i.d(t,"a",t),t},i.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},i.p="/",i(i.s=0)}([function(e,t,i){i(1),i(2),i(3),i(4),i(5),e.exports=i(6)},function(e,t,i){function n(e){return(n="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}"undefined"===n(Craft.SproutSeo)&&(Craft.SproutSeo={}),Craft.SproutSeo.EditableTable=Garnish.Base.extend({initialized:!1,id:null,baseName:null,columns:null,sorter:null,biggestId:-1,$table:null,$tbody:null,$addRowBtn:null,init:function(e,t,i,n){this.id=e,this.baseName=t,this.columns=i,this.setSettings(n,Craft.SproutSeo.EditableTable.defaults),this.$table=$("#"+e),this.$tbody=this.$table.children("tbody"),this.sorter=new Craft.DataTableSorter(this.$table,{helperClass:"editabletablesorthelper",copyDraggeeInputValuesToHelper:!0}),this.isVisible()?this.initialize():this.addListener(Garnish.$win,"resize","initializeIfVisible")},isVisible:function(){return this.$table.height()>0},initialize:function(){if(!this.initialized){this.initialized=!0,this.removeListener(Garnish.$win,"resize");for(var e=this.$tbody.children(),t=0;t<e.length;t++)new Craft.SproutSeo.EditableTable.Row(this,e[t]);this.$addRowBtn=this.$table.next(".buttons").children(".add"),this.addListener(this.$addRowBtn,"activate","addRow")}},initializeIfVisible:function(){this.isVisible()&&this.initialize()},addRow:function(){var e=this.settings.rowIdPrefix+(this.biggestId+1),t=Craft.SproutSeo.EditableTable.getRowHtml(e,this.columns,this.baseName,{}),i=$(t).appendTo(this.$tbody);new Craft.SproutSeo.EditableTable.Row(this,i),this.sorter.addItems(i),i.find("input,textarea,select").first().focus(),this.settings.onAddRow(i)}},{textualColTypes:["singleline","multiline","number"],defaults:{rowIdPrefix:"",onAddRow:$.noop,onDeleteRow:$.noop},getRowHtml:function(e,t,i,n){var a='<tr data-id="'+e+'">';for(var o in t){var r=t[o],s=i+"["+e+"]["+o+"]",l=void 0!==n[o]?n[o]:"";switch(a+='<td class="'+(Craft.inArray(r.type,Craft.SproutSeo.EditableTable.textualColTypes)?"textual":"")+" "+(void 0!==r.class?r.class:"")+'"'+(void 0!==r.width?' width="'+r.width+'"':"")+">",r.type){case"selectOther":a+=i.indexOf("ownership")>-1?'<div class="field sprout-selectother"><div class="select sprout-selectotherdropdown"><select onchange="setDefault(this)" name="'+s+'">':'<div class="field sprout-selectother"><div class="select sprout-selectotherdropdown"><select name="'+s+'">';var d=!1,u="disabled selected";for(var c in r.options){var h=r.options[c];if(void 0!==h.optgroup)d?a+="</optgroup>":d=!0,a+='<optgroup label="'+h.optgroup+'">';else{var f=void 0!==h.label?h.label:h,p=void 0!==h.value?h.value:c;a+="<option "+u+' value="'+p+'"'+(p===l?" selected":"")+(void 0!==h.disabled&&h.disabled?" disabled":"")+">"+f+"</option>"}u=""}d&&(a+="</optgroup>"),a+="</select></div>",a+='<div class="texticon clearable sprout-selectothertext hidden"><input class="text fullwidth" type="text" name="'+s+'" value="" autocomplete="off"><div class="clear" title="Clear"></div></div>',a+="</div>";break;case"checkbox":a+='<input type="hidden" name="'+s+'"><input type="checkbox" name="'+s+'" value="1"'+(l?" checked":"")+">";break;default:a+='<input class="text fullwidth" type="text" name="'+s+'" value="'+l+'">'}a+="</td>"}return a+='<td class="thin action"><a class="move icon" title="'+Craft.t("sprout-base-fields","Reorder")+'"></a></td><td class="thin action"><a class="delete icon" title="'+Craft.t("sprout-base-fields","Delete")+'"></a></td></tr>'}}),Craft.SproutSeo.EditableTable.Row=Garnish.Base.extend({table:null,id:null,niceTexts:null,$tr:null,$tds:null,$textareas:null,$deleteBtn:null,init:function(e,t){this.table=e,this.$tr=$(t),this.$tds=this.$tr.children();var i=parseInt(this.$tr.attr("data-id").substr(this.table.settings.rowIdPrefix.length));i>this.table.biggestId&&(this.table.biggestId=i),this.$textareas=$(),this.niceTexts=[];var n={},a=0;for(var o in this.table.columns){var r=this.table.columns[o];if(Craft.inArray(r.type,Craft.SproutSeo.EditableTable.textualColTypes)){var s=$("textarea",this.$tds[a]);this.$textareas=this.$textareas.add(s),this.addListener(s,"focus","onTextareaFocus"),this.addListener(s,"mousedown","ignoreNextTextareaFocus"),this.niceTexts.push(new Garnish.NiceText(s,{onHeightChange:$.proxy(this,"onTextareaHeightChange")})),"singleline"!==r.type&&"number"!==r.type||(this.addListener(s,"keypress",{type:r.type},"validateKeypress"),this.addListener(s,"textchange",{type:r.type},"validateValue")),n[o]=s}a++}for(var l in this.initSproutFields(),this.onTextareaHeightChange(),this.table.columns){var d=this.table.columns[l];d.autopopulate&&void 0!==n[d.autopopulate]&&!n[l].val()&&new Craft.HandleGenerator(n[l],n[d.autopopulate])}var u=this.$tr.children().last().find(".delete");this.addListener(u,"click","deleteRow")},initSproutFields:function(){Craft.SproutFields.initFields(this.$tr)},onTextareaFocus:function(e){this.onTextareaHeightChange();var t=$(e.currentTarget);t.data("ignoreNextFocus")?t.data("ignoreNextFocus",!1):setTimeout((function(){var e=t.val();if(void 0!==t[0].setSelectionRange){var i=2*e.length;t[0].setSelectionRange(0,i)}else t.val(e)}),0)},ignoreNextTextareaFocus:function(e){$.data(e.currentTarget,"ignoreNextFocus",!0)},validateKeypress:function(e){var t=e.keyCode?e.keyCode:e.charCode;Garnish.isCtrlKeyPressed(e)||t!==Garnish.RETURN_KEY&&("number"!==e.data.type||Craft.inArray(t,Craft.SproutSeo.EditableTable.Row.numericKeyCodes))||e.preventDefault()},validateValue:function(e){var t;if("number"===e.data.type){var i=e.currentTarget.value.match(/^\s*(-?[\d.]*)/);t=null!==i?i[1]:""}else t=e.currentTarget.value.replace(/[\r\n]/g,"");t!==e.currentTarget.value&&(e.currentTarget.value=t)},onTextareaHeightChange:function(){for(var e=-1,t=0;t<this.niceTexts.length;t++)this.niceTexts[t].height>e&&(e=this.niceTexts[t].height);this.$textareas.css("min-height",e);var i=this.$textareas.first().parent().height();i>e&&this.$textareas.css("min-height",i)},deleteRow:function(){this.table.sorter.removeItems(this.$tr),this.$tr.remove(),this.table.settings.onDeleteRow(this.$tr)}},{numericKeyCodes:[9,8,37,38,39,40,45,91,46,190,48,49,50,51,52,53,54,55,56,57]})},function(e,t){function i(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function n(e,t){for(var i=0;i<t.length;i++){var n=t[i];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}function a(e,t,i){return t&&n(e.prototype,t),i&&n(e,i),e}var o=function(){function e(t){i(this,e),this.fieldHandle=t.fieldHandle,this.seoBadgeInfo=t.seoBadgeInfo,this.maxDescriptionLength=t.maxDescriptionLength,this.initMetadataFieldButtons(),this.addSeoBadgesToUi()}return a(e,[{key:"initMetadataFieldButtons",value:function(){var e=this,t="fields-"+this.fieldHandle+"-meta-details-tabs";this.metaDetailsTabs=document.querySelectorAll("#"+t+" div.btn");var i="fields-"+this.fieldHandle+"-meta-details-body";this.metaDetailsBodyContainers=document.querySelectorAll("#"+i+" div.matrixblock");var n=!0,a=!1,o=void 0;try{for(var r,s=this.metaDetailsTabs[Symbol.iterator]();!(n=(r=s.next()).done);n=!0){r.value.addEventListener("click",(function(t){var i=$(t.target);i.is("div")||(i=i.closest("div.btn"));var n=$("#fields-projectsMetadata-meta-details-tabs .active");if(i.is(n))return!0;var a=i.attr("data-type"),o="#fields-"+e.fieldHandle+"-meta-details-body .fields-"+a,r=document.querySelector(o),s=!0,l=!1,d=void 0;try{for(var u,c=e.metaDetailsTabs[Symbol.iterator]();!(s=(u=c.next()).done);s=!0){u.value.classList.remove("active")}}catch(e){l=!0,d=e}finally{try{s||null==c.return||c.return()}finally{if(l)throw d}}var h=!0,f=!1,p=void 0;try{for(var v,y=e.metaDetailsBodyContainers[Symbol.iterator]();!(h=(v=y.next()).done);h=!0){v.value.style.display="none"}}catch(e){f=!0,p=e}finally{try{h||null==y.return||y.return()}finally{if(f)throw p}}$(r).show(),i.addClass("active")}))}}catch(e){a=!0,o=e}finally{try{n||null==s.return||s.return()}finally{if(a)throw o}}$(this.metaDetailsBodyContainers[0]).show(),this.metaDetailsTabs[0].classList.add("active")}},{key:"addSeoBadgesToUi",value:function(){for(var e in this.seoBadgeInfo){var t=this.seoBadgeInfo[e].type,i=this.seoBadgeInfo[e].handle,n=this.seoBadgeInfo[e].badgeClass,a=$("div."+n).html(),o="#fields-"+i+"-label",r=$("#fields-"+i+"-field input");if("title"===i&&(o="#title-label",r=$("#title")),this.appendSeoBadge(o,a),Craft.initUiElements($(o)),"optimizedTitleField"===t&&(r.attr("maxlength",60),new Garnish.NiceText(r,{showCharsLeft:!0})),"optimizedDescriptionField"===t){var s=$("#fields-"+i+"-field textarea");s.attr("maxlength",this.maxDescriptionLength),new Garnish.NiceText(s,{showCharsLeft:!0})}}}},{key:"getCustomizationSettings",value:function(e){return $("input[name='fields["+this.fieldHandle+"][metadata]["+e+"]']")}},{key:"appendSeoBadge",value:function(e,t){0===$(e).find(".sproutseo-info").length&&$(e).append(t).removeClass("hidden")}}]),e}(),r=function(){function e(t){i(this,e),this.keywordsFieldId=t.keywordsFieldId,this.initKeywordsField()}return a(e,[{key:"initKeywordsField",value:function(){$(this.keywordsFieldId+" input").tagEditor({animateDelete:20})}}]),e}();window.SproutSeoMetadataField=o,window.SproutSeoKeywordsField=r},function(e,t){function i(e,t){for(var i=0;i<t.length;i++){var n=t[i];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}var n=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e);var i=this;this.fieldHandle=t.fieldHandle,this.selectFieldId=t.selectFieldId;var n="#fields-"+this.fieldHandle+"-meta-details-body "+this.selectFieldId,a=document.querySelector(n),o=a.options[a.selectedIndex].value;this.currentContainerId=this.getTargetContainerId(o),this.currentContainer=document.getElementById(this.currentContainerId),this.currentContainer&&this.currentContainer.classList.remove("hidden"),a.addEventListener("change",(function(e){i.toggleOpenGraphFieldContainer(e,i)}))}var t,n,a;return t=e,(n=[{key:"toggleOpenGraphFieldContainer",value:function(e,t){var i=e.target,n=i.options[i.selectedIndex].value,a=t.getTargetContainerId(n),o=document.getElementById(a);o&&o.classList.remove("hidden"),t.currentContainer&&t.currentContainer.classList.add("hidden"),t.currentContainerId=a,t.currentContainer=o}},{key:"getTargetContainerId",value:function(e){return"#fields-"+e}}])&&i(t.prototype,n),a&&i(t,a),e}();window.MetaDetailsToggle=n},function(e,t){function i(e,t){for(var i=0;i<t.length;i++){var n=t[i];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}var n=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this.items=t.items,this.mainEntityValues=t.mainEntityValues,this.initLegacyCode(),this.initOtherLegacyCode()}var t,n,a;return t=e,(n=[{key:"initLegacyCode",value:function(){var e=this,t=$(".mainentity-firstdropdown select"),i=$(".mainentity-seconddropdown select"),n="";t.on("change",(function(){var a,o;n=t.val(),a=$(".organization-info :input"),o=1,$.each(a,(function(e,t){e>=o&&$(t).html("")})),function(e,t){$.each(e,(function(e,i){e>=t&&$(i).closest("div.organizationinfo-dropdown").addClass("hidden")}))}($(".organization-info :input"),1),void 0===e.items[n]||""===n||e.items[n].length<=0||e.items[n]&&(i.closest("div.organizationinfo-dropdown").removeClass("hidden"),function(e,t){var i,n="";$.each(t,(function(e,t){n=e.replace(/([A-Z][^A-Z\b])/g," $1").trim(),i+='<option value="'+e+'">'+n+"</option>",t&&$.each(t,(function(e,t){n="&nbsp;&nbsp;&nbsp;"+e.replace(/([A-Z][^A-Z\b])/g," $1").trim(),i+='<option value="'+e+'">'+n+"</option>"}))})),e.append(i)}(i,e.items[n]))})),i.on("change",(function(){i.val()}))}},{key:"initOtherLegacyCode",value:function(){var e=this.mainEntityValues;$(".mainentity-firstdropdown select").change((function(){"barrelstrength-sproutseo-schema-personschema"===this.value?$(".mainentity-seconddropdown select").addClass("hidden"):$(".mainentity-seconddropdown select").removeClass("hidden")})),e&&(e.hasOwnProperty("schemaTypeId")&&e.schemaTypeId&&$(".mainentity-firstdropdown select").val(e.schemaTypeId).change(),e.hasOwnProperty("schemaOverrideTypeId")&&e.schemaOverrideTypeId&&$(".mainentity-seconddropdown select").val(e.schemaOverrideTypeId).change())}}])&&i(t.prototype,n),a&&i(t,a),e}();window.SproutSeoWebsiteIdentitySettings=n},function(e,t){function i(e,t){for(var i=0;i<t.length;i++){var n=t[i];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}var n=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this.items=t.items,this.websiteIdentity=t.websiteIdentity,this.firstDropdownId=t.firstDropdownId,this.secondDropdownId=t.secondDropdownId,this.thirdDropdownId=t.thirdDropdownId,this.initWebsiteIdentityField(),this.moreWebsiteIdentityStuff()}var t,n,a;return t=e,(n=[{key:"initWebsiteIdentityField",value:function(){var e=this,t=$(this.firstDropdownId),i=$(this.secondDropdownId),n=$(this.thirdDropdownId),a="",o="";t.on("change",(function(){a=t.val(),e.clearDropDown($("#organization :input"),1),e.disableDropDown($("#organization :input"),1),""!==a&&e.items[a].hasOwnProperty("children")&&(e.enableDropDown(i),e.generateOptions(i,e.items[a].children))})),i.on("change",(function(){if(a=$("#first").val(),o=i.val(),e.clearDropDown($("#organization :input"),2),e.disableDropDown($("#organization :input"),2),""!==o)for(var t=e.items[a].children,r=0;r<t.length;r++)if(t[r].name===o){t[r].hasOwnProperty("children")&&(e.enableDropDown(n),e.generateOptions(n,t[r].children));break}})),n.on("change",(function(){n.val()}))}},{key:"moreWebsiteIdentityStuff",value:function(){var e=this.websiteIdentity;e&&(e.hasOwnProperty("organizationSubTypes")&&e.organizationSubTypes[0]&&$("#first").val(e.organizationSubTypes[0]).change(),e.hasOwnProperty("organizationSubTypes")&&e.organizationSubTypes[1]&&$("#second").val(e.organizationSubTypes[1]).change(),e.hasOwnProperty("organizationSubTypes")&&e.organizationSubTypes[2]&&$("#third").val(e.organizationSubTypes[2]).change()),$("#identityType").change((function(){"Person"===this.value?($(".person-info").removeClass("hidden"),$(".organization-info").addClass("hidden")):($(".person-info").addClass("hidden"),$(".organization-info").removeClass("hidden")),"Organization"===this.value?($(".organization-info").removeClass("hidden"),$(".person-info").addClass("hidden"),"LocalBusiness"==$("#first").val()&&$("#localbusiness").removeClass("hidden")):($(".organization-info").addClass("hidden"),$(".person-info").removeClass("hidden"))})),$("#first").change((function(){"LocalBusiness"===this.value?$("#localbusiness").removeClass("hidden"):$("#localbusiness").addClass("hidden")}))}},{key:"clearDropDown",value:function(e,t){$.each(e,(function(e,i){e>=t&&$(i).html('<option value="" selected="selected"></option>')}))}},{key:"disableDropDown",value:function(e,t){$.each(e,(function(e,i){e>=t&&$(i).closest("div.organizationinfo-dropdown").addClass("hidden")}))}},{key:"enableDropDown",value:function(e){e.closest("div.organizationinfo-dropdown").removeClass("hidden")}},{key:"generateOptions",value:function(e,t){for(var i="",n="",a=0;a<t.length;a++)n=t[a].name.replace(/([A-Z][^A-Z\b])/g," $1").trim(),i+='<option value="'+t[a].name+'">'+n+"</option>";e.append(i)}}])&&i(t.prototype,n),a&&i(t,a),e}();window.SproutSeoWebsiteIdentity=n},function(e,t){}]);