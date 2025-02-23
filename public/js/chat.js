/*
 *
 * More info at [www.dropzonejs.com](http://www.dropzonejs.com)
 *
 * Copyright (c) 2012, Matias Meno
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
(function(){var e,t,i,n,r,s,o,l,a=[].slice,u={}.hasOwnProperty,p=function(e,t){function i(){this.constructor=e}for(var n in t)u.call(t,n)&&(e[n]=t[n]);return i.prototype=t.prototype,e.prototype=new i,e.__super__=t.prototype,e};o=function(){},t=function(){function e(){}return e.prototype.addEventListener=e.prototype.on,e.prototype.on=function(e,t){return this._callbacks=this._callbacks||{},this._callbacks[e]||(this._callbacks[e]=[]),this._callbacks[e].push(t),this},e.prototype.emit=function(){var e,t,i,n,r,s;if(n=arguments[0],e=2<=arguments.length?a.call(arguments,1):[],this._callbacks=this._callbacks||{},i=this._callbacks[n])for(r=0,s=i.length;r<s;r++)t=i[r],t.apply(this,e);return this},e.prototype.removeListener=e.prototype.off,e.prototype.removeAllListeners=e.prototype.off,e.prototype.removeEventListener=e.prototype.off,e.prototype.off=function(e,t){var i,n,r,s,o;if(!this._callbacks||0===arguments.length)return this._callbacks={},this;if(n=this._callbacks[e],!n)return this;if(1===arguments.length)return delete this._callbacks[e],this;for(r=s=0,o=n.length;s<o;r=++s)if(i=n[r],i===t){n.splice(r,1);break}return this},e}(),e=function(e){function i(e,t){var r,s,o;if(this.element=e,this.version=i.version,this.defaultOptions.previewTemplate=this.defaultOptions.previewTemplate.replace(/\n*/g,""),this.clickableElements=[],this.listeners=[],this.files=[],"string"==typeof this.element&&(this.element=document.querySelector(this.element)),!this.element||null==this.element.nodeType)throw new Error("Invalid dropzone element.");if(this.element.dropzone)throw new Error("Dropzone already attached.");if(i.instances.push(this),this.element.dropzone=this,r=null!=(o=i.optionsForElement(this.element))?o:{},this.options=n({},this.defaultOptions,r,null!=t?t:{}),this.options.forceFallback||!i.isBrowserSupported())return this.options.fallback.call(this);if(null==this.options.url&&(this.options.url=this.element.getAttribute("action")),!this.options.url)throw new Error("No URL provided.");if(this.options.acceptedFiles&&this.options.acceptedMimeTypes)throw new Error("You can't provide both 'acceptedFiles' and 'acceptedMimeTypes'. 'acceptedMimeTypes' is deprecated.");this.options.acceptedMimeTypes&&(this.options.acceptedFiles=this.options.acceptedMimeTypes,delete this.options.acceptedMimeTypes),this.options.method=this.options.method.toUpperCase(),(s=this.getExistingFallback())&&s.parentNode&&s.parentNode.removeChild(s),this.options.previewsContainer!==!1&&(this.options.previewsContainer?this.previewsContainer=i.getElement(this.options.previewsContainer,"previewsContainer"):this.previewsContainer=this.element),this.options.clickable&&(this.options.clickable===!0?this.clickableElements=[this.element]:this.clickableElements=i.getElements(this.options.clickable,"clickable")),this.init()}var n,r;return p(i,e),i.prototype.Emitter=t,i.prototype.events=["drop","dragstart","dragend","dragenter","dragover","dragleave","addedfile","addedfiles","removedfile","thumbnail","error","errormultiple","processing","processingmultiple","uploadprogress","totaluploadprogress","sending","sendingmultiple","success","successmultiple","canceled","canceledmultiple","complete","completemultiple","reset","maxfilesexceeded","maxfilesreached","queuecomplete"],i.prototype.defaultOptions={url:null,method:"post",withCredentials:!1,parallelUploads:2,uploadMultiple:!1,maxFilesize:256,paramName:"file",createImageThumbnails:!0,maxThumbnailFilesize:10,thumbnailWidth:120,thumbnailHeight:120,filesizeBase:1e3,maxFiles:null,params:{},clickable:!0,ignoreHiddenFiles:!0,acceptedFiles:null,acceptedMimeTypes:null,autoProcessQueue:!0,autoQueue:!0,addRemoveLinks:!1,previewsContainer:null,hiddenInputContainer:"body",capture:null,renameFilename:null,dictDefaultMessage:"Drop files here to upload",dictFallbackMessage:"Your browser does not support drag'n'drop file uploads.",dictFallbackText:"Please use the fallback form below to upload your files like in the olden days.",dictFileTooBig:"File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",dictInvalidFileType:"You can't upload files of this type.",dictResponseError:"Server responded with {{statusCode}} code.",dictCancelUpload:"Cancel upload",dictCancelUploadConfirmation:"Are you sure you want to cancel this upload?",dictRemoveFile:"Remove file",dictRemoveFileConfirmation:null,dictMaxFilesExceeded:"You can not upload any more files.",accept:function(e,t){return t()},init:function(){return o},forceFallback:!1,fallback:function(){var e,t,n,r,s,o;for(this.element.className=""+this.element.className+" dz-browser-not-supported",o=this.element.getElementsByTagName("div"),r=0,s=o.length;r<s;r++)e=o[r],/(^| )dz-message($| )/.test(e.className)&&(t=e,e.className="dz-message");return t||(t=i.createElement('<div class="dz-message"><span></span></div>'),this.element.appendChild(t)),n=t.getElementsByTagName("span")[0],n&&(null!=n.textContent?n.textContent=this.options.dictFallbackMessage:null!=n.innerText&&(n.innerText=this.options.dictFallbackMessage)),this.element.appendChild(this.getFallbackForm())},resize:function(e){var t,i,n;return t={srcX:0,srcY:0,srcWidth:e.width,srcHeight:e.height},i=e.width/e.height,t.optWidth=this.options.thumbnailWidth,t.optHeight=this.options.thumbnailHeight,null==t.optWidth&&null==t.optHeight?(t.optWidth=t.srcWidth,t.optHeight=t.srcHeight):null==t.optWidth?t.optWidth=i*t.optHeight:null==t.optHeight&&(t.optHeight=1/i*t.optWidth),n=t.optWidth/t.optHeight,e.height<t.optHeight||e.width<t.optWidth?(t.trgHeight=t.srcHeight,t.trgWidth=t.srcWidth):i>n?(t.srcHeight=e.height,t.srcWidth=t.srcHeight*n):(t.srcWidth=e.width,t.srcHeight=t.srcWidth/n),t.srcX=(e.width-t.srcWidth)/2,t.srcY=(e.height-t.srcHeight)/2,t},drop:function(e){return this.element.classList.remove("dz-drag-hover")},dragstart:o,dragend:function(e){return this.element.classList.remove("dz-drag-hover")},dragenter:function(e){return this.element.classList.add("dz-drag-hover")},dragover:function(e){return this.element.classList.add("dz-drag-hover")},dragleave:function(e){return this.element.classList.remove("dz-drag-hover")},paste:o,reset:function(){return this.element.classList.remove("dz-started")},addedfile:function(e){var t,n,r,s,o,l,a,u,p,d,c,h,m;if(this.element===this.previewsContainer&&this.element.classList.add("dz-started"),this.previewsContainer){for(e.previewElement=i.createElement(this.options.previewTemplate.trim()),e.previewTemplate=e.previewElement,this.previewsContainer.appendChild(e.previewElement),d=e.previewElement.querySelectorAll("[data-dz-name]"),s=0,a=d.length;s<a;s++)t=d[s],t.textContent=this._renameFilename(e.name);for(c=e.previewElement.querySelectorAll("[data-dz-size]"),o=0,u=c.length;o<u;o++)t=c[o],t.innerHTML=this.filesize(e.size);for(this.options.addRemoveLinks&&(e._removeLink=i.createElement('<a class="dz-remove" href="javascript:undefined;" data-dz-remove>'+this.options.dictRemoveFile+"</a>"),e.previewElement.appendChild(e._removeLink)),n=function(t){return function(n){return n.preventDefault(),n.stopPropagation(),e.status===i.UPLOADING?i.confirm(t.options.dictCancelUploadConfirmation,function(){return t.removeFile(e)}):t.options.dictRemoveFileConfirmation?i.confirm(t.options.dictRemoveFileConfirmation,function(){return t.removeFile(e)}):t.removeFile(e)}}(this),h=e.previewElement.querySelectorAll("[data-dz-remove]"),m=[],l=0,p=h.length;l<p;l++)r=h[l],m.push(r.addEventListener("click",n));return m}},removedfile:function(e){var t;return e.previewElement&&null!=(t=e.previewElement)&&t.parentNode.removeChild(e.previewElement),this._updateMaxFilesReachedClass()},thumbnail:function(e,t){var i,n,r,s;if(e.previewElement){for(e.previewElement.classList.remove("dz-file-preview"),s=e.previewElement.querySelectorAll("[data-dz-thumbnail]"),n=0,r=s.length;n<r;n++)i=s[n],i.alt=e.name,i.src=t;return setTimeout(function(t){return function(){return e.previewElement.classList.add("dz-image-preview")}}(this),1)}},error:function(e,t){var i,n,r,s,o;if(e.previewElement){for(e.previewElement.classList.add("dz-error"),"String"!=typeof t&&t.error&&(t=t.error),s=e.previewElement.querySelectorAll("[data-dz-errormessage]"),o=[],n=0,r=s.length;n<r;n++)i=s[n],o.push(i.textContent=t);return o}},errormultiple:o,processing:function(e){if(e.previewElement&&(e.previewElement.classList.add("dz-processing"),e._removeLink))return e._removeLink.textContent=this.options.dictCancelUpload},processingmultiple:o,uploadprogress:function(e,t,i){var n,r,s,o,l;if(e.previewElement){for(o=e.previewElement.querySelectorAll("[data-dz-uploadprogress]"),l=[],r=0,s=o.length;r<s;r++)n=o[r],"PROGRESS"===n.nodeName?l.push(n.value=t):l.push(n.style.width=""+t+"%");return l}},totaluploadprogress:o,sending:o,sendingmultiple:o,success:function(e){if(e.previewElement)return e.previewElement.classList.add("dz-success")},successmultiple:o,canceled:function(e){return this.emit("error",e,"Upload canceled.")},canceledmultiple:o,complete:function(e){if(e._removeLink&&(e._removeLink.textContent=this.options.dictRemoveFile),e.previewElement)return e.previewElement.classList.add("dz-complete")},completemultiple:o,maxfilesexceeded:o,maxfilesreached:o,queuecomplete:o,addedfiles:o,previewTemplate:'<div class="dz-preview dz-file-preview">\n  <div class="dz-image"><img data-dz-thumbnail /></div>\n  <div class="dz-details">\n    <div class="dz-size"><span data-dz-size></span></div>\n    <div class="dz-filename"><span data-dz-name></span></div>\n  </div>\n  <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>\n  <div class="dz-error-message"><span data-dz-errormessage></span></div>\n  <div class="dz-success-mark">\n    <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">\n      <title>Check</title>\n      <defs></defs>\n      <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">\n        <path d="M23.5,31.8431458 L17.5852419,25.9283877 C16.0248253,24.3679711 13.4910294,24.366835 11.9289322,25.9289322 C10.3700136,27.4878508 10.3665912,30.0234455 11.9283877,31.5852419 L20.4147581,40.0716123 C20.5133999,40.1702541 20.6159315,40.2626649 20.7218615,40.3488435 C22.2835669,41.8725651 24.794234,41.8626202 26.3461564,40.3106978 L43.3106978,23.3461564 C44.8771021,21.7797521 44.8758057,19.2483887 43.3137085,17.6862915 C41.7547899,16.1273729 39.2176035,16.1255422 37.6538436,17.6893022 L23.5,31.8431458 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z" id="Oval-2" stroke-opacity="0.198794158" stroke="#747474" fill-opacity="0.816519475" fill="#FFFFFF" sketch:type="MSShapeGroup"></path>\n      </g>\n    </svg>\n  </div>\n  <div class="dz-error-mark">\n    <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">\n      <title>Error</title>\n      <defs></defs>\n      <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">\n        <g id="Check-+-Oval-2" sketch:type="MSLayerGroup" stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF" fill-opacity="0.816519475">\n          <path d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z" id="Oval-2" sketch:type="MSShapeGroup"></path>\n        </g>\n      </g>\n    </svg>\n  </div>\n</div>'},n=function(){var e,t,i,n,r,s,o;for(n=arguments[0],i=2<=arguments.length?a.call(arguments,1):[],s=0,o=i.length;s<o;s++){t=i[s];for(e in t)r=t[e],n[e]=r}return n},i.prototype.getAcceptedFiles=function(){var e,t,i,n,r;for(n=this.files,r=[],t=0,i=n.length;t<i;t++)e=n[t],e.accepted&&r.push(e);return r},i.prototype.getRejectedFiles=function(){var e,t,i,n,r;for(n=this.files,r=[],t=0,i=n.length;t<i;t++)e=n[t],e.accepted||r.push(e);return r},i.prototype.getFilesWithStatus=function(e){var t,i,n,r,s;for(r=this.files,s=[],i=0,n=r.length;i<n;i++)t=r[i],t.status===e&&s.push(t);return s},i.prototype.getQueuedFiles=function(){return this.getFilesWithStatus(i.QUEUED)},i.prototype.getUploadingFiles=function(){return this.getFilesWithStatus(i.UPLOADING)},i.prototype.getAddedFiles=function(){return this.getFilesWithStatus(i.ADDED)},i.prototype.getActiveFiles=function(){var e,t,n,r,s;for(r=this.files,s=[],t=0,n=r.length;t<n;t++)e=r[t],e.status!==i.UPLOADING&&e.status!==i.QUEUED||s.push(e);return s},i.prototype.init=function(){var e,t,n,r,s,o,l;for("form"===this.element.tagName&&this.element.setAttribute("enctype","multipart/form-data"),this.element.classList.contains("dropzone")&&!this.element.querySelector(".dz-message")&&this.element.appendChild(i.createElement('<div class="dz-default dz-message"><span>'+this.options.dictDefaultMessage+"</span></div>")),this.clickableElements.length&&(n=function(e){return function(){return e.hiddenFileInput&&e.hiddenFileInput.parentNode.removeChild(e.hiddenFileInput),e.hiddenFileInput=document.createElement("input"),e.hiddenFileInput.setAttribute("type","file"),(null==e.options.maxFiles||e.options.maxFiles>1)&&e.hiddenFileInput.setAttribute("multiple","multiple"),e.hiddenFileInput.className="dz-hidden-input",null!=e.options.acceptedFiles&&e.hiddenFileInput.setAttribute("accept",e.options.acceptedFiles),null!=e.options.capture&&e.hiddenFileInput.setAttribute("capture",e.options.capture),e.hiddenFileInput.style.visibility="hidden",e.hiddenFileInput.style.position="absolute",e.hiddenFileInput.style.top="0",e.hiddenFileInput.style.left="0",e.hiddenFileInput.style.height="0",e.hiddenFileInput.style.width="0",document.querySelector(e.options.hiddenInputContainer).appendChild(e.hiddenFileInput),e.hiddenFileInput.addEventListener("change",function(){var t,i,r,s;if(i=e.hiddenFileInput.files,i.length)for(r=0,s=i.length;r<s;r++)t=i[r],e.addFile(t);return e.emit("addedfiles",i),n()})}}(this))(),this.URL=null!=(o=window.URL)?o:window.webkitURL,l=this.events,r=0,s=l.length;r<s;r++)e=l[r],this.on(e,this.options[e]);return this.on("uploadprogress",function(e){return function(){return e.updateTotalUploadProgress()}}(this)),this.on("removedfile",function(e){return function(){return e.updateTotalUploadProgress()}}(this)),this.on("canceled",function(e){return function(t){return e.emit("complete",t)}}(this)),this.on("complete",function(e){return function(t){if(0===e.getAddedFiles().length&&0===e.getUploadingFiles().length&&0===e.getQueuedFiles().length)return setTimeout(function(){return e.emit("queuecomplete")},0)}}(this)),t=function(e){return e.stopPropagation(),e.preventDefault?e.preventDefault():e.returnValue=!1},this.listeners=[{element:this.element,events:{dragstart:function(e){return function(t){return e.emit("dragstart",t)}}(this),dragenter:function(e){return function(i){return t(i),e.emit("dragenter",i)}}(this),dragover:function(e){return function(i){var n;try{n=i.dataTransfer.effectAllowed}catch(r){}return i.dataTransfer.dropEffect="move"===n||"linkMove"===n?"move":"copy",t(i),e.emit("dragover",i)}}(this),dragleave:function(e){return function(t){return e.emit("dragleave",t)}}(this),drop:function(e){return function(i){return t(i),e.drop(i)}}(this),dragend:function(e){return function(t){return e.emit("dragend",t)}}(this)}}],this.clickableElements.forEach(function(e){return function(t){return e.listeners.push({element:t,events:{click:function(n){return(t!==e.element||n.target===e.element||i.elementInside(n.target,e.element.querySelector(".dz-message")))&&e.hiddenFileInput.click(),!0}}})}}(this)),this.enable(),this.options.init.call(this)},i.prototype.destroy=function(){var e;return this.disable(),this.removeAllFiles(!0),(null!=(e=this.hiddenFileInput)?e.parentNode:void 0)&&(this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=null),delete this.element.dropzone,i.instances.splice(i.instances.indexOf(this),1)},i.prototype.updateTotalUploadProgress=function(){var e,t,i,n,r,s,o,l;if(n=0,i=0,e=this.getActiveFiles(),e.length){for(l=this.getActiveFiles(),s=0,o=l.length;s<o;s++)t=l[s],n+=t.upload.bytesSent,i+=t.upload.total;r=100*n/i}else r=100;return this.emit("totaluploadprogress",r,i,n)},i.prototype._getParamName=function(e){return"function"==typeof this.options.paramName?this.options.paramName(e):""+this.options.paramName+(this.options.uploadMultiple?"["+e+"]":"")},i.prototype._renameFilename=function(e){return"function"!=typeof this.options.renameFilename?e:this.options.renameFilename(e)},i.prototype.getFallbackForm=function(){var e,t,n,r;return(e=this.getExistingFallback())?e:(n='<div class="dz-fallback">',this.options.dictFallbackText&&(n+="<p>"+this.options.dictFallbackText+"</p>"),n+='<input type="file" name="'+this._getParamName(0)+'" '+(this.options.uploadMultiple?'multiple="multiple"':void 0)+' /><input type="submit" value="Upload!"></div>',t=i.createElement(n),"FORM"!==this.element.tagName?(r=i.createElement('<form action="'+this.options.url+'" enctype="multipart/form-data" method="'+this.options.method+'"></form>'),r.appendChild(t)):(this.element.setAttribute("enctype","multipart/form-data"),this.element.setAttribute("method",this.options.method)),null!=r?r:t)},i.prototype.getExistingFallback=function(){var e,t,i,n,r,s;for(t=function(e){var t,i,n;for(i=0,n=e.length;i<n;i++)if(t=e[i],/(^| )fallback($| )/.test(t.className))return t},s=["div","form"],n=0,r=s.length;n<r;n++)if(i=s[n],e=t(this.element.getElementsByTagName(i)))return e},i.prototype.setupEventListeners=function(){var e,t,i,n,r,s,o;for(s=this.listeners,o=[],n=0,r=s.length;n<r;n++)e=s[n],o.push(function(){var n,r;n=e.events,r=[];for(t in n)i=n[t],r.push(e.element.addEventListener(t,i,!1));return r}());return o},i.prototype.removeEventListeners=function(){var e,t,i,n,r,s,o;for(s=this.listeners,o=[],n=0,r=s.length;n<r;n++)e=s[n],o.push(function(){var n,r;n=e.events,r=[];for(t in n)i=n[t],r.push(e.element.removeEventListener(t,i,!1));return r}());return o},i.prototype.disable=function(){var e,t,i,n,r;for(this.clickableElements.forEach(function(e){return e.classList.remove("dz-clickable")}),this.removeEventListeners(),n=this.files,r=[],t=0,i=n.length;t<i;t++)e=n[t],r.push(this.cancelUpload(e));return r},i.prototype.enable=function(){return this.clickableElements.forEach(function(e){return e.classList.add("dz-clickable")}),this.setupEventListeners()},i.prototype.filesize=function(e){var t,i,n,r,s,o,l,a;if(n=0,r="b",e>0){for(o=["TB","GB","MB","KB","b"],i=l=0,a=o.length;l<a;i=++l)if(s=o[i],t=Math.pow(this.options.filesizeBase,4-i)/10,e>=t){n=e/Math.pow(this.options.filesizeBase,4-i),r=s;break}n=Math.round(10*n)/10}return"<strong>"+n+"</strong> "+r},i.prototype._updateMaxFilesReachedClass=function(){return null!=this.options.maxFiles&&this.getAcceptedFiles().length>=this.options.maxFiles?(this.getAcceptedFiles().length===this.options.maxFiles&&this.emit("maxfilesreached",this.files),this.element.classList.add("dz-max-files-reached")):this.element.classList.remove("dz-max-files-reached")},i.prototype.drop=function(e){var t,i;e.dataTransfer&&(this.emit("drop",e),t=e.dataTransfer.files,this.emit("addedfiles",t),t.length&&(i=e.dataTransfer.items,i&&i.length&&null!=i[0].webkitGetAsEntry?this._addFilesFromItems(i):this.handleFiles(t)))},i.prototype.paste=function(e){var t,i;if(null!=(null!=e&&null!=(i=e.clipboardData)?i.items:void 0))return this.emit("paste",e),t=e.clipboardData.items,t.length?this._addFilesFromItems(t):void 0},i.prototype.handleFiles=function(e){var t,i,n,r;for(r=[],i=0,n=e.length;i<n;i++)t=e[i],r.push(this.addFile(t));return r},i.prototype._addFilesFromItems=function(e){var t,i,n,r,s;for(s=[],n=0,r=e.length;n<r;n++)i=e[n],null!=i.webkitGetAsEntry&&(t=i.webkitGetAsEntry())?t.isFile?s.push(this.addFile(i.getAsFile())):t.isDirectory?s.push(this._addFilesFromDirectory(t,t.name)):s.push(void 0):null!=i.getAsFile&&(null==i.kind||"file"===i.kind)?s.push(this.addFile(i.getAsFile())):s.push(void 0);return s},i.prototype._addFilesFromDirectory=function(e,t){var i,n,r;return i=e.createReader(),n=function(e){return"undefined"!=typeof console&&null!==console?("function"==typeof console.log,void 0):void 0},(r=function(e){return function(){return i.readEntries(function(i){var n,s,o;if(i.length>0){for(s=0,o=i.length;s<o;s++)n=i[s],n.isFile?n.file(function(i){if(!e.options.ignoreHiddenFiles||"."!==i.name.substring(0,1))return i.fullPath=""+t+"/"+i.name,e.addFile(i)}):n.isDirectory&&e._addFilesFromDirectory(n,""+t+"/"+n.name);r()}return null},n)}}(this))()},i.prototype.accept=function(e,t){return e.size>1024*this.options.maxFilesize*1024?t(this.options.dictFileTooBig.replace("{{filesize}}",Math.round(e.size/1024/10.24)/100).replace("{{maxFilesize}}",this.options.maxFilesize)):i.isValidFile(e,this.options.acceptedFiles)?null!=this.options.maxFiles&&this.getAcceptedFiles().length>=this.options.maxFiles?(t(this.options.dictMaxFilesExceeded.replace("{{maxFiles}}",this.options.maxFiles)),this.emit("maxfilesexceeded",e)):this.options.accept.call(this,e,t):t(this.options.dictInvalidFileType)},i.prototype.addFile=function(e){return e.upload={progress:0,total:e.size,bytesSent:0},this.files.push(e),e.status=i.ADDED,this.emit("addedfile",e),this._enqueueThumbnail(e),this.accept(e,function(t){return function(i){return i?(e.accepted=!1,t._errorProcessing([e],i)):(e.accepted=!0,t.options.autoQueue&&t.enqueueFile(e)),t._updateMaxFilesReachedClass()}}(this))},i.prototype.enqueueFiles=function(e){var t,i,n;for(i=0,n=e.length;i<n;i++)t=e[i],this.enqueueFile(t);return null},i.prototype.enqueueFile=function(e){if(e.status!==i.ADDED||e.accepted!==!0)throw new Error("This file can't be queued because it has already been processed or was rejected.");if(e.status=i.QUEUED,this.options.autoProcessQueue)return setTimeout(function(e){return function(){return e.processQueue()}}(this),0)},i.prototype._thumbnailQueue=[],i.prototype._processingThumbnail=!1,i.prototype._enqueueThumbnail=function(e){if(this.options.createImageThumbnails&&e.type.match(/image.*/)&&e.size<=1024*this.options.maxThumbnailFilesize*1024)return this._thumbnailQueue.push(e),setTimeout(function(e){return function(){return e._processThumbnailQueue()}}(this),0)},i.prototype._processThumbnailQueue=function(){if(!this._processingThumbnail&&0!==this._thumbnailQueue.length)return this._processingThumbnail=!0,this.createThumbnail(this._thumbnailQueue.shift(),function(e){return function(){return e._processingThumbnail=!1,e._processThumbnailQueue()}}(this))},i.prototype.removeFile=function(e){if(e.status===i.UPLOADING&&this.cancelUpload(e),this.files=l(this.files,e),this.emit("removedfile",e),0===this.files.length)return this.emit("reset")},i.prototype.removeAllFiles=function(e){var t,n,r,s;for(null==e&&(e=!1),s=this.files.slice(),n=0,r=s.length;n<r;n++)t=s[n],(t.status!==i.UPLOADING||e)&&this.removeFile(t);return null},i.prototype.createThumbnail=function(e,t){var i;return i=new FileReader,i.onload=function(n){return function(){return"image/svg+xml"===e.type?(n.emit("thumbnail",e,i.result),void(null!=t&&t())):n.createThumbnailFromUrl(e,i.result,t)}}(this),i.readAsDataURL(e)},i.prototype.createThumbnailFromUrl=function(e,t,i,n){var r;return r=document.createElement("img"),n&&(r.crossOrigin=n),r.onload=function(t){return function(){var n,o,l,a,u,p,d,c;if(e.width=r.width,e.height=r.height,l=t.options.resize.call(t,e),null==l.trgWidth&&(l.trgWidth=l.optWidth),null==l.trgHeight&&(l.trgHeight=l.optHeight),n=document.createElement("canvas"),o=n.getContext("2d"),n.width=l.trgWidth,n.height=l.trgHeight,s(o,r,null!=(u=l.srcX)?u:0,null!=(p=l.srcY)?p:0,l.srcWidth,l.srcHeight,null!=(d=l.trgX)?d:0,null!=(c=l.trgY)?c:0,l.trgWidth,l.trgHeight),a=n.toDataURL("image/png"),t.emit("thumbnail",e,a),null!=i)return i()}}(this),null!=i&&(r.onerror=i),r.src=t},i.prototype.processQueue=function(){var e,t,i,n;if(t=this.options.parallelUploads,i=this.getUploadingFiles().length,e=i,!(i>=t)&&(n=this.getQueuedFiles(),n.length>0)){if(this.options.uploadMultiple)return this.processFiles(n.slice(0,t-i));for(;e<t;){if(!n.length)return;this.processFile(n.shift()),e++}}},i.prototype.processFile=function(e){return this.processFiles([e])},i.prototype.processFiles=function(e){var t,n,r;for(n=0,r=e.length;n<r;n++)t=e[n],t.processing=!0,t.status=i.UPLOADING,this.emit("processing",t);return this.options.uploadMultiple&&this.emit("processingmultiple",e),this.uploadFiles(e)},i.prototype._getFilesWithXhr=function(e){var t,i;return i=function(){var i,n,r,s;for(r=this.files,s=[],i=0,n=r.length;i<n;i++)t=r[i],t.xhr===e&&s.push(t);return s}.call(this)},i.prototype.cancelUpload=function(e){var t,n,r,s,o,l,a;if(e.status===i.UPLOADING){for(n=this._getFilesWithXhr(e.xhr),r=0,o=n.length;r<o;r++)t=n[r],t.status=i.CANCELED;for(e.xhr.abort(),s=0,l=n.length;s<l;s++)t=n[s],this.emit("canceled",t);this.options.uploadMultiple&&this.emit("canceledmultiple",n)}else(a=e.status)!==i.ADDED&&a!==i.QUEUED||(e.status=i.CANCELED,this.emit("canceled",e),this.options.uploadMultiple&&this.emit("canceledmultiple",[e]));if(this.options.autoProcessQueue)return this.processQueue()},r=function(){var e,t;return t=arguments[0],e=2<=arguments.length?a.call(arguments,1):[],"function"==typeof t?t.apply(this,e):t},i.prototype.uploadFile=function(e){return this.uploadFiles([e])},i.prototype.uploadFiles=function(e){var t,s,o,l,a,u,p,d,c,h,m,f,g,v,y,F,w,E,b,C,k,z,L,x,A,T,D,S,_,M,I,N,U,R;for(b=new XMLHttpRequest,C=0,x=e.length;C<x;C++)t=e[C],t.xhr=b;f=r(this.options.method,e),w=r(this.options.url,e),b.open(f,w,!0),b.withCredentials=!!this.options.withCredentials,y=null,o=function(i){return function(){var n,r,s;for(s=[],n=0,r=e.length;n<r;n++)t=e[n],s.push(i._errorProcessing(e,y||i.options.dictResponseError.replace("{{statusCode}}",b.status),b));return s}}(this),F=function(i){return function(n){var r,s,o,l,a,u,p,d,c;if(null!=n)for(s=100*n.loaded/n.total,o=0,u=e.length;o<u;o++)t=e[o],t.upload={progress:s,total:n.total,bytesSent:n.loaded};else{for(r=!0,s=100,l=0,p=e.length;l<p;l++)t=e[l],100===t.upload.progress&&t.upload.bytesSent===t.upload.total||(r=!1),t.upload.progress=s,t.upload.bytesSent=t.upload.total;if(r)return}for(c=[],a=0,d=e.length;a<d;a++)t=e[a],c.push(i.emit("uploadprogress",t,s,t.upload.bytesSent));return c}}(this),b.onload=function(t){return function(n){var r;if(e[0].status!==i.CANCELED&&4===b.readyState){if(y=b.responseText,b.getResponseHeader("content-type")&&~b.getResponseHeader("content-type").indexOf("application/json"))try{y=JSON.parse(y)}catch(s){n=s,y="Invalid JSON response from server."}return F(),200<=(r=b.status)&&r<300?t._finished(e,y,n):o()}}}(this),b.onerror=function(t){return function(){if(e[0].status!==i.CANCELED)return o()}}(this),v=null!=(_=b.upload)?_:b,v.onprogress=F,u={Accept:"application/json","Cache-Control":"no-cache","X-Requested-With":"XMLHttpRequest"},this.options.headers&&n(u,this.options.headers);for(l in u)a=u[l],a&&b.setRequestHeader(l,a);if(s=new FormData,this.options.params){M=this.options.params;for(m in M)E=M[m],s.append(m,E)}for(k=0,A=e.length;k<A;k++)t=e[k],this.emit("sending",t,b,s);if(this.options.uploadMultiple&&this.emit("sendingmultiple",e,b,s),"FORM"===this.element.tagName)for(I=this.element.querySelectorAll("input, textarea, select, button"),z=0,T=I.length;z<T;z++)if(d=I[z],c=d.getAttribute("name"),h=d.getAttribute("type"),"SELECT"===d.tagName&&d.hasAttribute("multiple"))for(N=d.options,L=0,D=N.length;L<D;L++)g=N[L],g.selected&&s.append(c,g.value);else(!h||"checkbox"!==(U=h.toLowerCase())&&"radio"!==U||d.checked)&&s.append(c,d.value);for(p=S=0,R=e.length-1;0<=R?S<=R:S>=R;p=0<=R?++S:--S)s.append(this._getParamName(p),e[p],this._renameFilename(e[p].name));return this.submitRequest(b,s,e)},i.prototype.submitRequest=function(e,t,i){return e.send(t)},i.prototype._finished=function(e,t,n){var r,s,o;for(s=0,o=e.length;s<o;s++)r=e[s],r.status=i.SUCCESS,this.emit("success",r,t,n),this.emit("complete",r);if(this.options.uploadMultiple&&(this.emit("successmultiple",e,t,n),this.emit("completemultiple",e)),this.options.autoProcessQueue)return this.processQueue()},i.prototype._errorProcessing=function(e,t,n){var r,s,o;for(s=0,o=e.length;s<o;s++)r=e[s],r.status=i.ERROR,this.emit("error",r,t,n),this.emit("complete",r);if(this.options.uploadMultiple&&(this.emit("errormultiple",e,t,n),this.emit("completemultiple",e)),this.options.autoProcessQueue)return this.processQueue()},i}(t),e.version="4.3.0",e.options={},e.optionsForElement=function(t){return t.getAttribute("id")?e.options[i(t.getAttribute("id"))]:void 0},e.instances=[],e.forElement=function(e){if("string"==typeof e&&(e=document.querySelector(e)),null==(null!=e?e.dropzone:void 0))throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize. Use the `init` option to setup any additional observers on your Dropzone.");return e.dropzone},e.autoDiscover=!0,e.discover=function(){var t,i,n,r,s,o;for(document.querySelectorAll?n=document.querySelectorAll(".dropzone"):(n=[],t=function(e){var t,i,r,s;for(s=[],i=0,r=e.length;i<r;i++)t=e[i],/(^| )dropzone($| )/.test(t.className)?s.push(n.push(t)):s.push(void 0);return s},t(document.getElementsByTagName("div")),t(document.getElementsByTagName("form"))),o=[],r=0,s=n.length;r<s;r++)i=n[r],e.optionsForElement(i)!==!1?o.push(new e(i)):o.push(void 0);return o},e.blacklistedBrowsers=[/opera.*Macintosh.*version\/12/i],e.isBrowserSupported=function(){var t,i,n,r,s;if(t=!0,window.File&&window.FileReader&&window.FileList&&window.Blob&&window.FormData&&document.querySelector)if("classList"in document.createElement("a"))for(s=e.blacklistedBrowsers,n=0,r=s.length;n<r;n++)i=s[n],i.test(navigator.userAgent)&&(t=!1);else t=!1;else t=!1;return t},l=function(e,t){var i,n,r,s;for(s=[],n=0,r=e.length;n<r;n++)i=e[n],i!==t&&s.push(i);return s},i=function(e){return e.replace(/[\-_](\w)/g,function(e){return e.charAt(1).toUpperCase()})},e.createElement=function(e){var t;return t=document.createElement("div"),t.innerHTML=e,t.childNodes[0]},e.elementInside=function(e,t){if(e===t)return!0;for(;e=e.parentNode;)if(e===t)return!0;return!1},e.getElement=function(e,t){var i;if("string"==typeof e?i=document.querySelector(e):null!=e.nodeType&&(i=e),null==i)throw new Error("Invalid `"+t+"` option provided. Please provide a CSS selector or a plain HTML element.");return i},e.getElements=function(e,t){var i,n,r,s,o,l,a,u;if(e instanceof Array){r=[];try{for(s=0,l=e.length;s<l;s++)n=e[s],r.push(this.getElement(n,t))}catch(p){i=p,r=null}}else if("string"==typeof e)for(r=[],u=document.querySelectorAll(e),o=0,a=u.length;o<a;o++)n=u[o],r.push(n);else null!=e.nodeType&&(r=[e]);if(null==r||!r.length)throw new Error("Invalid `"+t+"` option provided. Please provide a CSS selector, a plain HTML element or a list of those.");return r},e.confirm=function(e,t,i){return window.confirm(e)?t():null!=i?i():void 0},e.isValidFile=function(e,t){var i,n,r,s,o;if(!t)return!0;for(t=t.split(","),n=e.type,i=n.replace(/\/.*$/,""),s=0,o=t.length;s<o;s++)if(r=t[s],r=r.trim(),"."===r.charAt(0)){if(e.name.toLowerCase().indexOf(r.toLowerCase(),e.name.length-r.length)!==-1)return!0}else if(/\/\*$/.test(r)){
if(i===r.replace(/\/.*$/,""))return!0}else if(n===r)return!0;return!1},"undefined"!=typeof jQuery&&null!==jQuery&&(jQuery.fn.dropzone=function(t){return this.each(function(){return new e(this,t)})}),"undefined"!=typeof module&&null!==module?module.exports=e:window.Dropzone=e,e.ADDED="added",e.QUEUED="queued",e.ACCEPTED=e.QUEUED,e.UPLOADING="uploading",e.PROCESSING=e.UPLOADING,e.CANCELED="canceled",e.ERROR="error",e.SUCCESS="success",r=function(e){var t,i,n,r,s,o,l,a,u,p;for(l=e.naturalWidth,o=e.naturalHeight,i=document.createElement("canvas"),i.width=1,i.height=o,n=i.getContext("2d"),n.drawImage(e,0,0),r=n.getImageData(0,0,1,o).data,p=0,s=o,a=o;a>p;)t=r[4*(a-1)+3],0===t?s=a:p=a,a=s+p>>1;return u=a/o,0===u?1:u},s=function(e,t,i,n,s,o,l,a,u,p){var d;return d=r(t),e.drawImage(t,i,n,s,o,l,a,u,p/d)},n=function(e,t){var i,n,r,s,o,l,a,u,p;if(r=!1,p=!0,n=e.document,u=n.documentElement,i=n.addEventListener?"addEventListener":"attachEvent",a=n.addEventListener?"removeEventListener":"detachEvent",l=n.addEventListener?"":"on",s=function(i){if("readystatechange"!==i.type||"complete"===n.readyState)return("load"===i.type?e:n)[a](l+i.type,s,!1),!r&&(r=!0)?t.call(e,i.type||i):void 0},o=function(){var e;try{u.doScroll("left")}catch(t){return e=t,void setTimeout(o,50)}return s("poll")},"complete"!==n.readyState){if(n.createEventObject&&u.doScroll){try{p=!e.frameElement}catch(d){}p&&o()}return n[i](l+"DOMContentLoaded",s,!1),n[i](l+"readystatechange",s,!1),e[i](l+"load",s,!1)}},e._autoDiscoverFunction=function(){if(e.autoDiscover)return e.discover()},n(window,e._autoDiscoverFunction)}).call(this);


$(document).ready(function(){
  
	var vue = new Vue({
		el: '#chat',
		data : {
			messages: [],
			newMessage: "",
			userName: username,
			port: chatport,
			uri: url,
			conn: false,
			user: "",
      mine: "",
      media_url: '',
      temp_media_url: '',
      media_type: '',
      mediaDropzone: '',
      items: allMessages,
    },
    created : function () {
      const vueVm = this;

      Dropzone.options.mediaDropzone = false; // disabling the auto detect of dropzone js
      setTimeout(() => {
        /* Initialize Dropzone */
        vueVm.mediaDropzone = new Dropzone('#media-dropzone', {
          paramName: "medium",
          parallelUploads: 1,
          maxFiles: 1,
          maxFilesize: 50,
          acceptedFiles:
            "image/png, image/jpeg, image/jpg, video/avi, video/mp4, video/flv, video/mov",
          dictDefaultMessage:
            `
          <h5 style="font-weight: 300;">Drop images/videos here to upload</h5>
            <i class="material-icons">insert_photo</i>
            <i class="material-icons">movie</i>
          <br>
          <h5 style="font-weight: 300;">Or just click anywhere in this container to choose file</h5>
        `,
          previewTemplate: document.getElementById("custom-preview").innerHTML,
          init: function () {
            const dropzoneVm = this;

            dropzoneVm.on('success', (file, response) => {
              // get the returned json from the back end
              const mediaObject = response;
              vueVm.media_type = mediaObject.media_type;
              vueVm.temp_media_url = mediaObject.media_url;

            })
          }
        });
      }, 0)
    },
    
		mounted : function(){
			// default port
			this.port = this.port.length == 0 ? '9090' : this.port;

			// init connection
			this.conn = (location.protocol === 'https:') ? new WebSocket('wss://'+this.uri+'/chat') : new WebSocket('ws://'+this.uri+'/chat');
			var me = this;

			this.conn.onclose = function (event) {
        me.addSystemMessage("Something went wrong. Please try again later!");
      };

			this.conn.onopen = function(event) {
        var message = {
          connect: true,
          userId: userid
        };
        this.conn.send(JSON.stringify(message));
			}.bind(this);

			this.conn.onmessage = function(event) {
        me.addServerMessage(event.data);
      };

      setTimeout(() => {
        var chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }, 100);
		},
		methods : {
			addSystemMessage : function(message){
				this.addMessage({
					"msg" 	: message,
					"class"	: "system",
					"who"	: "System",
					"dir"	: "in",
				});
			},
			addServerMessage : function(message){
        message = JSON.parse(message);

        if (message.media_type) {
          this.addMessage({
            "msg": '',
            "media_url": message.media_url,
            "media_type": message.media_type,
            "class": "mine",
            "who": "",
            "dir": "in",
          });
        }
        else {
          this.addMessage({
            "msg": message.message,
            "class": "system",
            "who": "System",
            "dir": "in",
          });
        }
			},
			addMeAmessage : function(message){
        message = JSON.parse(message);

        /* 
          Add the send message to sender (in UI form)
        */
        if (this.media_url) {
          this.addMessage({
            "msg": '',
            "media_url": this.media_url,
            "media_type": this.media_type,
            "class"	: "mine",
            "who"	: "",
            "dir"	: "out",
          });
        }
        else {
          this.addMessage({
            "msg": message.message,
            "class": "mine",
            "who": "",
            "dir": "out",
          });
        }
			},
			addMessage : function(message) {
				this.messages.push(message);

				if($('#thread-'+threadid).length)
          $('#thread-collection').prepend($('#thread-'+threadid));

				// allow the DOM to get updated
        setTimeout(() => {
          Vue.nextTick(function () {
            this.scrollMessagesDown();
          }.bind(this));
        }, 0)
			},
			scrollMessagesDown : function(){
				var chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
      },
			sendMessage : function() {

        if (threadid == '') return;

        // send only when there is a message or there is a media to be sent
        if (this.newMessage.length || this.temp_media_url) {
          // can send

          // create the message object to be send in Chat.php
          var message = {};
          message.from_id = userid;
          message.to_id = threadid;
          message.from = username;

          // assign what time of message to send
          if (this.newMessage) {
            // if message is text not media
            message.message = this.newMessage;
            message.media_url = '';
            message.media_type = '';
          }
          else if (this.temp_media_url) {
            /*
              Upon uploading, the image url will be bind to a temporary
              variable, and then later bind in the actual variable that is
              seen in the html.

              This is to avoid early binding/showing of image in the browser
            */

            if (this.media_url) {
              this.media_url = '';
              this.media_type = '';
            }
            
            this.media_url = this.temp_media_url;

            // if message is media and not text
            message.message = '';
            message.media_url = this.media_url;
            message.media_type = this.media_type;
          }

          // identify who is the sender
          if (usertype == 'Customer') message.direction = 0;
          else if (usertype == 'Breeder') message.direction = 1;
          else message.direction = 2;
        
          var msgToSend = JSON.stringify(message);

          this.conn.send(msgToSend); // send to user 

          this.addMeAmessage(msgToSend); // send dto self
        
          this.newMessage = ""; // clear the message area after sending

          if ((this.mediaDropzone.files).length) {
            
            /* 
              clear dropzone preview file(s)
              so that when the user clicks send, the files will be emptied
            */
            this.media_url = null;
            this.media_type = '';
            this.media_url = '';
            this.mediaDropzone.removeAllFiles(true);

            // close modal after clicking the send button
            $('#upload-media-modal').modal('close');
          }
        }
        else return;
			},
			focusMe : function(event) {
				event.target.select();
      },
      uploadMedia: function() {
        $('#upload-media-modal').modal('open'); // opens modal for uploading picture
      }
		}
	});


});

//# sourceMappingURL=chat.js.map
