"use strict";

function htmlentities(string)
{
	var chars=new Array ('&','\"','<','>');
	var entities=new Array ('amp','quot','lt','gt');
	var i;
	for (i=0;i<chars.length;i++)
	{
		var myRegExp=new RegExp();
		if (myRegExp.compile)
		{
			myRegExp.compile(chars[i],'g')
		} else
		{
			myRegExp=new RegExp(chars[i],'g');
		}
		string=string.replace(myRegExp,'&'+entities[i]+';');
	}
	return string;
}

var tree_list=function(name,hook,root_id,options,multi_selection,tabindex,all_nodes_selectable,use_server_id)
{
	if (typeof window.do_ajax_request=='undefined') return;

	if ((typeof multi_selection=='undefined') || (!multi_selection)) var multi_selection=false;

	this.name=name;
	this.hook=hook;
	this.options=options;
	this.multi_selection=multi_selection;
	this.tabindex=tabindex?tabindex:null;
	this.all_nodes_selectable=all_nodes_selectable;
	this.use_server_id=use_server_id;

	var element=document.getElementById('tree_list__root_'+name);
	setInnerHTML(element,'<div class="ajax_tree_list_loading"><img class="inline_image_2" src="'+'{$IMG*;,bottom/loading}'.replace(/^http:/,window.location.protocol)+'" alt="" /> {!LOADING^;}</div>');

	// Initial rendering
	do_ajax_request('{$BASE_URL_NOHTTP;}/'+hook+'&id='+window.encodeURIComponent(root_id)+'&options='+options+'&default='+window.encodeURIComponent(document.getElementById(name).value),this,false);
}

tree_list.prototype.tree_list_data='';
tree_list.prototype.busy=false;
tree_list.prototype.last_clicked=null; // The hyperlink object that was last clicked (usage during multi selection when holding down shift)

{$,Go through our tree list looking for a particular XML node}
tree_list.prototype.getElementByIdHack=function(id,type,ob,serverid)
{
	if ((typeof type=='undefined') || (!type)) var type='c';
	if ((typeof ob=='undefined') || (!ob)) var ob=this.tree_list_data;
	var i,test,done=false;
	// Normally we could only ever use getElementsByTagName, but Konqueror and Safari don't like it
	try // IE9 beta has serious problems
	{
		if ((typeof ob.getElementsByTagName!='undefined') && (typeof ob.getElementsByTagName!='unknown'))
		{
			var results=ob.getElementsByTagName((type=='c')?'category':'entry');
			for (i=0;i<results.length;i++)
			{
				if ((typeof results[i].getAttribute!="undefined") && (typeof results[i].getAttribute!="unknown") && (results[i].getAttribute(serverid?'serverid':'id')==id))
				{
					return results[i];
				}
			}
			done=true;
		}
	}
	catch (e) {};
	if (!done)
	{
		for (i=0;i<ob.childNodes.length;i++)
		{
			if (ob.childNodes[i].nodeName.toLowerCase()=='category')
			{
				test=this.getElementByIdHack(id,type,ob.childNodes[i],serverid);
				if (test)
				{
					return test;
				}
			}
			if ((ob.childNodes[i].nodeName.toLowerCase()==((type=='c')?'category':'entry')) && (ob.childNodes[i].getAttribute(serverid?'serverid':'id')==id))
				return ob.childNodes[i];
		}
	}
	return null;
}

tree_list.prototype.response=function(ajax_result_frame,ajax_result,expanding_id)
{
	if (!window.fixUpNodePositions) return;

	ajax_result=carefulImportNode(ajax_result);

	var i,xml,temp_node,html;
	if (!expanding_id)
	{
		html=document.getElementById('tree_list__root_'+this.name);
		setInnerHTML(html,'');

		this.tree_list_data=ajax_result.cloneNode(true);
		xml=this.tree_list_data;

		if (xml.childNodes.length==0)
		{
			var error=document.createTextNode((this.name.indexOf('category')==-1 && window.location.href.indexOf('category')==-1)?'{!NO_ENTRIES^;}':'{!NO_CATEGORIES^;}');
			html.className='error_marker';
			html.appendChild(error);
			return;
		}
	} else
	{
		xml=this.getElementByIdHack(expanding_id,'c');
		for (i=0;i<ajax_result.childNodes.length;i++)
		{
			temp_node=ajax_result.childNodes[i];
			if (temp_node.nodeName!='#text') xml.appendChild(temp_node.cloneNode(true));
		}
		html=document.getElementById(this.name+'tree_list_c_'+expanding_id);
	}

	attributesFullFixup(xml);

	this.root_element=this.render_tree(xml,html);

	var name=this.name;
	fixUpNodePositions(name);
	//window.setTimeout(function() { fixUpNodePositions(name); },500);
}

function attributesFullFixup(xml)
{
	var node,i;
	if (typeof window.attributes_full=='undefined') window.attributes_full={};
	var id=xml.getAttribute('id');
	if (typeof window.attributes_full[id]=='undefined') window.attributes_full[id]={};
	for (i=0;i<xml.attributes.length;i++)
	{
		window.attributes_full[id][xml.attributes[i].name]=xml.attributes[i].value;
	}
	for (i=0;i<xml.childNodes.length;i++)
	{
		node=xml.childNodes[i];

		if (node.nodeName=='#text') continue; // A text-node

		if (node.nodeName.toLowerCase()=='attribute') // Attribute hack, to allow Opera to work (retroactively added - in hindsight, would have done this in a cleaner way)
		{
			window.attributes_full[id][node.getAttribute('key')]=node.getAttribute('value');
		} else
		if ((node.nodeName.toLowerCase()=='category') || (node.nodeName.toLowerCase()=='entry'))
		{
			attributesFullFixup(node);
		}
	}
}

tree_list.prototype.render_tree=function(xml,html,element)
{
	if (!window.fixUpNodePositions) return null;

	var i,colour,new_html,url,escaped_title;
	var initially_expanded,selectable,extra,url,title,func,temp,master_html,node,node_self_wrap,node_self;
	if ((typeof element=='undefined') || (!element)) var element=document.getElementById(this.name);

	if (typeof window.nereidFade!='undefined')
	{
		setOpacity(html,0.0);
		nereidFade(html,100,30,4);
	}

	html.style.display='block';
	if (xml.childNodes.length==0) html.style.display='none';

	for (i=0;i<xml.childNodes.length;i++)
	{
		node=xml.childNodes[i];
		if (node.nodeName=='#text') continue; // A text-node
		if (node.nodeName.toLowerCase()=='attribute') continue;

		// Special handling of 'expand' nodes, which say to pre-expand some categories as soon as the page loads
		if (node.nodeName=='expand')
		{
			var e=document.getElementById(this.name+'texp_c_'+getInnerHTML(node));
			if (e)
			{
				var html_node=document.getElementById(this.name+'tree_list_c_'+getInnerHTML(node));
				var expanding=(html_node.style.display!='block');
				if (expanding)
					e.onmousedown(null,true);
			} else
			{
				// Now try against serverid
				var xml_node=this.getElementByIdHack(getInnerHTML(node),'c',null,true);
				if (xml_node)
				{
					var e=document.getElementById(this.name+'texp_c_'+xml_node.getAttribute('id'));
					if (e)
					{
						var html_node=document.getElementById(this.name+'tree_list_c_'+xml_node.getAttribute('id'));
						var expanding=(html_node.style.display!='block');
						if (expanding)
							e.onmousedown(null,true);
					}
				}
			}
			continue;
		}

		// Category or entry nodes
		extra=' ';
		func=node.getAttribute('img_func_1');
		if (func)
		{
			extra=extra+eval(func+'(node)');
		}
		func=node.getAttribute('img_func_2');
		if (func)
		{
			extra=extra+eval(func+'(node)');
		}
		node_self_wrap=document.createElement('div');
		node_self=document.createElement('div');
		node_self.style.display='inline';
		node_self_wrap.appendChild(node_self);
		node_self.object=this;
		colour=(node.getAttribute('selectable')=='true' || this.all_nodes_selectable)?'black':'gray';
		selectable=(node.getAttribute('selectable')=='true' || this.all_nodes_selectable);
		if (node.nodeName.toLowerCase()=='category')
		{
			// Render self
			node_self.className=(node.getAttribute('highlighted')=='true')?'tree_list_highlighted':'tree_list_nonhighlighted';
			initially_expanded=(node.getAttribute('has_children')!='true') || (node.getAttribute('expanded')=='true');
			escaped_title=escape_html((typeof node.getAttribute('title')!='undefined')?node.getAttribute('title'):'');
			if (escaped_title=='') escaped_title='{!NA_EM^;}';
			var description='';
			var description_in_use='';
			if (node.getAttribute('description_html'))
			{
				description=node.getAttribute('description_html');
				description_in_use=escape_html(description);
			} else
			{
				if (node.getAttribute('description')) description=escape_html('. '+node.getAttribute('description'));
				description_in_use=escaped_title+': {!TREE_LIST_SELECT^;*}'+description+((node.getAttribute('serverid')=='')?(' ('+escape_html(node.getAttribute('serverid'))+')'):'');
			}
			setInnerHTML(node_self,'<input style="width: 11px; height: 11px"'+(this.tabindex?(' tabindex="'+this.tabindex+'"'):'')+' type="image" alt="'+((!initially_expanded)?'{!EXPAND_TREE_LIST^;}':'{!CONTRACT_TREE_LIST^;}')+': '+escaped_title+'" title="'+((!initially_expanded)?'{!EXPAND^;}':'{!CONTRACT^;}')+'" id="'+this.name+'texp_c_'+node.getAttribute('id')+'" src="'+((!initially_expanded)?'{$IMG*;,treefield/plus}':'{$IMG*;,treefield/minus}').replace(/^http:/,window.location.protocol)+'" /> <img style="width: 14px; height: 14px" class="treelist_cat_icon" alt="{!CATEGORY^;}" src="'+'{$IMG*;,treefield/category}'.replace(/^http:/,window.location.protocol)+'" /> <label id="'+this.name+'tsel_c_'+node.getAttribute('id')+'" for="'+this.name+'tsel_r_'+node.getAttribute('id')+'" onmouseout="if (typeof window.deactivateTooltip!=\'undefined\') deactivateTooltip(this,event);" onmousemove="if (typeof window.activateTooltip!=\'undefined\') repositionTooltip(this,event);" onmouseover="if (typeof window.activateTooltip!=\'undefined\') activateTooltip(this,event,'+(node.getAttribute('description_html')?'':'escape_html')+'(this.childNodes[0].title),\'auto\');" style="background-color: white; color: black; cursor: pointer; color: '+colour+'"><input '+(this.tabindex?('tabindex="'+this.tabindex+'" '):'')+'id="'+this.name+'tsel_r_'+node.getAttribute('id')+'" style="position: absolute; left: -10000px" type="radio" name="_'+this.name+'" value="1" title="'+description_in_use+'" />'+escaped_title+'</label> <span id="'+this.name+'extra_'+node.getAttribute('id')+'">'+extra+'</span><br />');
			var img=node_self.getElementsByTagName('input')[0];
			img.onmousedown=this.handle_tree_click;
			img.onmouseup=function() { return false; };
			img.oncontextmenu=function() { return false; };
			img.onclick=function() { return false; };
			img.object=this;
			var a=node_self.getElementsByTagName('label')[0];
			img.onkeypress=a.onkeypress=a.childNodes[0].onkeypress=function(img) { return function(event) { if (typeof event=='undefined') var event=window.event; if (((event.keyCode?event.keyCode:event.charCode)==13) || ['+','-'].inArray(String.fromCharCode(event.keyCode?event.keyCode:event.charCode))) img.onmousedown(event); } } (img);
			a.oncontextmenu=function() { return false; };
			a.handle_selection=this.handle_selection;
			a.childNodes[0].onfocus=function() { this.parentNode.style.outline='1px dotted'; };
			a.childNodes[0].onblur=function() { this.parentNode.style.outline=''; };
			a.childNodes[0].onclick=a.handle_selection;
			a.childNodes[0].object=this;
			a.object=this;
			a.onmousedown=function(event) { // To disable selection of text when holding shift or control
				if (typeof event=='undefined') var event=window.event;
				if (typeof event.preventDefault!='undefined') event.preventDefault();
			}
			html.appendChild(node_self_wrap);

			// Do any children
			new_html=document.createElement('div');
			new_html.role='treeitem';
			new_html.id=this.name+'tree_list_c_'+node.getAttribute('id');
			new_html.style.display=((!initially_expanded) || (node.getAttribute('has_children')!='true'))?'none':'block';
			new_html.style.padding{$WCASE,{!en_left}}='15px';
			var selected=((this.use_server_id?node.getAttribute('serverid'):node.getAttribute('id'))==element.value) || node.getAttribute('selected')=='yes';
			if (selectable)
			{
				this.make_element_look_selected(document.getElementById(this.name+'tsel_c_'+node.getAttribute('id')),selected);
				if (selected)
				{
					element.value=node.getAttribute('id'); // Copy in proper ID for what is selected, not relying on what we currently have as accurate
					if (element.value!='')
					{
						if (element.selected_title!='') element.selected_title+=',';
						element.selected_title+=node.getAttribute('title');
					}
					if (element.onchange) element.onchange();
					if (typeof element.fakeonchange!='undefined' && element.fakeonchange) element.fakeonchange();
				}
			}
			node_self.appendChild(new_html);

			// Auto-expand
			if (window.ctrlPressed || window.altPressed || window.metaPressed || window.shiftPressed)
			{
				if (!initially_expanded)
					img.onmousedown();
			}
		} else // Assume entry
		{
			escaped_title=escape_html((typeof node.getAttribute('title')!='undefined')?node.getAttribute('title'):'');
			if (escaped_title=='') escaped_title='{!NA_EM^;}';

			var description='';
			var description_in_use='';
			if (node.getAttribute('description_html'))
			{
				description=node.getAttribute('description_html');
				description_in_use=escape_html(description);
			} else
			{
				if (node.getAttribute('description')) description=escape_html('. '+node.getAttribute('description'));
				description_in_use=escaped_title+': {!TREE_LIST_SELECT^;*}'+description+((node.getAttribute('serverid')=='')?(' ('+escape_html(node.getAttribute('serverid'))+')'):'');
			}

			// Render self
			initially_expanded=false;
			setInnerHTML(node_self,'<img alt="{!ENTRY^;}" title="" src="'+'{$IMG*;,treefield/entry}'.replace(/^http:/,window.location.protocol)+'" style="width: 14px; height: 14px; padding-left: 16px" /> <label id="'+this.name+'tsel_e_'+node.getAttribute('id')+'" style="background-color: white; color: black; cursor: pointer; color: '+colour+'" for="'+this.name+'tsel_s_'+node.getAttribute('id')+'" onmouseout="if (typeof window.deactivateTooltip!=\'undefined\') deactivateTooltip(this,event);" onmousemove="if (typeof window.activateTooltip!=\'undefined\') repositionTooltip(this,event);" onmouseover="if (typeof window.activateTooltip!=\'undefined\') activateTooltip(this,event,'+(node.getAttribute('description_html')?'':'escape_html')+'(\''+(description_in_use.replace(/\n/g,'').replace(/'/g,'\\'+'\''))+'\'),\'800px\');"><input'+(this.tabindex?(' tabindex="'+this.tabindex+'"'):'')+' id="'+this.name+'tsel_s_'+node.getAttribute('id')+'" style="position: absolute; left: -10000px" type="radio" name="_'+this.name+'" value="1" />'+escaped_title+'</label>'+extra+'<br />');
			var a=node_self.getElementsByTagName('label')[0];
			a.handle_selection=this.handle_selection;
			a.childNodes[0].onfocus=function() { this.parentNode.style.outline='1px dotted'; };
			a.childNodes[0].onblur=function() { this.parentNode.style.outline=''; };
			a.childNodes[0].onclick=a.handle_selection;
			a.childNodes[0].object=this;
			a.object=this;
			a.onmousedown=function(event) { // To disable selection of text when holding shift or control
				if (typeof event=='undefined') var event=window.event;
				if (typeof event.preventDefault!='undefined') event.preventDefault();
			}
			html.appendChild(node_self_wrap);
			var selected=((this.use_server_id?node.getAttribute('serverid'):node.getAttribute('id'))==element.value) || node.getAttribute('selected')=='yes';
			this.make_element_look_selected(document.getElementById(this.name+'tsel_e_'+node.getAttribute('id')),selected);
		}

		if ((node.getAttribute('draggable')) && (node.getAttribute('draggable')!='false') && (window.Drag))
		{
/*			if ((!initially_expanded) || (!node.getAttribute('has_children')) || (node.getAttribute('has_children')=='false'))
				node_self.style.position='absolute';
			node_self_wrap.style.height=findHeight(node_self)+'px';*/
			master_html=document.getElementById('tree_list__root_'+this.name);
			fixUpNodePosition(node_self);
			node_self.ocp_draggable=node.getAttribute('draggable');
			Drag.init(node_self,null,findPosX(master_html,true),findPosX(master_html,true)+findWidth(master_html)-findWidth(node_self)-5,findPosY(master_html,true));
			node_self.onDragEnd=function(x,y)
				{
					this.style.background='transparent';
					this.style.position='static';
					//setOpacity(this,1.0);

					if (this.lastHit!=null)
					{
						this.lastHit.parentNode.parentNode.style.border='0px';
						if (this.parentNode.parentNode!=this.lastHit)
						{
							var xml_node=this.object.getElementByIdHack(this.getElementsByTagName('input')[0].id.substr(7+this.object.name.length));
							var target_xml_node=this.object.getElementByIdHack(this.lastHit.id.substr(12+this.object.name.length));

							if ((this.lastHit.childNodes.length==1) && (this.lastHit.childNodes[0].nodeName=='#text'))
							{
								setInnerHTML(this.lastHit,'');
								this.object.render_tree(target_xml_node,this.lastHit);
							}

							// Change HTML
							this.parentNode.parentNode.removeChild(this.parentNode);
							this.lastHit.appendChild(this.parentNode);

							// Change node structure
							xml_node.parentNode.removeChild(xml_node);
							target_xml_node.appendChild(xml_node);

							// Ajax request
							eval('drag_'+xml_node.getAttribute('draggable')+'("'+xml_node.getAttribute('serverid')+'","'+target_xml_node.getAttribute('serverid')+'")');

							fixUpNodePositions(this.object.name);
						}
					}

					fixUpNodePosition(this);
				}
			node_self.onDragStart=function(x,y)
				{
				}
			node_self.onDrag=function(x,y)
				{
					this.style.backgroundColor='white';
					this.style.position='absolute';
					//setOpacity(this,0.7);
					var hit=find_overlapping_selectable(this,this.object.tree_list_data,this.object.name);
					if (this.lastHit) this.lastHit.parentNode.parentNode.style.border='0px';
					if (hit!=null)
					{
						this.lastHit=hit;
						hit.parentNode.parentNode.style.border='1px solid green';
					}
				}
		}

		if (initially_expanded)
		{
			this.render_tree(node,new_html,element);
		} else
		{
			if (new_html) setInnerHTML(new_html,'{!PLEASE_WAIT^;}',true);
		}
	}

	trigger_resize();

	return a;
}

function fixUpNodePositions(name)
{
	var html=document.getElementById('tree_list__root_'+name);
	var toFix=html.getElementsByTagName('div');
	var i;
	for (i=0;i<toFix.length;i++)
	{
		if (toFix[i].style.position=='absolute') fixUpNodePosition(toFix[i]);
	}
}

function fixUpNodePosition(node_self)
{
	node_self.style.left=findPosX(node_self.parentNode,true)+'px';
	node_self.style.top=findPosY(node_self.parentNode,true)+'px';
}

function find_overlapping_selectable(element,node,name)
{
	var i,childNode,temp,childNodeElement,my_y,y,height;
	my_y=findPosY(element,true);
	for (i=0;i<node.childNodes.length;i++)
	{
		childNode=node.childNodes[i];
		if (childNode.getAttribute('droppable')==element.ocp_draggable)
		{
			childNodeElement=document.getElementById(name+'tree_list_'+((childNode.nodeName.toLowerCase()=='category')?'c':'e')+'_'+childNode.getAttribute('id'));
			y=findPosY(childNodeElement.parentNode.parentNode,true);
			height=findHeight(childNodeElement.parentNode.parentNode);
			if ((y<my_y) && (y+height>my_y))
			{
				return childNodeElement;
			}
		}
		if (childNode.getAttribute('expanded')=='true')
		{
			temp=find_overlapping_selectable(element,childNode,name);
			if (temp) return temp;
		}
	}
	return null;
}

tree_list.prototype.handle_tree_click=function(event,automated) // Not called as a method
{
	if (typeof window.do_ajax_request=='undefined') return false;

	var element=document.getElementById(this.object.name);
	if (element.disabled) return false;

	if (this.object.busy) return false;
	this.object.busy=true;

	var clicked_id=this.getAttribute('id').substr(7+this.object.name.length);

	var html_node=document.getElementById(this.object.name+'tree_list_c_'+clicked_id);
	var img=document.getElementById(this.object.name+'texp_c_'+clicked_id);

	var expanding=(html_node.style.display!='block');

	if (expanding)
	{
		var xml_node=this.object.getElementByIdHack(clicked_id,'c');
		xml_node.setAttribute('expanded','true');
		var real_clicked_id=xml_node.getAttribute('serverid');
		if ((typeof real_clicked_id).toLowerCase()!='string') real_clicked_id=clicked_id;

		/*if ((xml_node.getAttribute('draggable')) && (xml_node.getAttribute('draggable')!='false'))
		{
			html_node.parentNode.style.position='static';
		}*/

		if ((xml_node.getAttribute('has_children')=='true') && (xml_node.childNodes.length==0))
		{
			var url='{$BASE_URL_NOHTTP;}/'+this.object.hook+'&id='+window.encodeURIComponent(real_clicked_id)+'&options='+this.object.options+'&default='+window.encodeURIComponent(element.value);
			var ob=this.object;
			do_ajax_request(url,function (ajax_result_frame,ajax_result) { setInnerHTML(html_node,''); ob.response(ajax_result_frame,ajax_result,clicked_id); });
			setInnerHTML(html_node,'<div{$?,{$VALUE_OPTION,html5}, aria-busy="true"}><img class="inline_image_2" src="'+'{$IMG*;;,bottom/loading}'.replace(/^http:/,window.location.protocol)+'" alt="" /> {!LOADING^;}</div>');
			var container=document.getElementById('tree_list__root_'+ob.name);
			if ((automated) && (container) && (container.style.overflowY=='auto'))
			{
				window.setTimeout(function() {
					container.scrollTop=findPosY(html_node)-20;
				}, 0);
			}
		}

		html_node.style.display='block';
		if (typeof window.nereidFade!='undefined')
		{
			setOpacity(html_node,0.0);
			nereidFade(html_node,100,30,4);
		}

		img.src='{$IMG,treefield/minus}'.replace(/^http:/,window.location.protocol);
		img.title=img.title.replace('{!EXPAND_TREE_LIST^;}','{!CONTRACT_TREE_LIST^;}');
		img.alt=img.alt.replace('{!EXPAND_TREE_LIST^;}','{!CONTRACT_TREE_LIST^;}');
	} else
	{
		var xml_node=this.object.getElementByIdHack(clicked_id,'c');
		xml_node.setAttribute('expanded','false');

		/*if ((xml_node.getAttribute('draggable')) && (xml_node.getAttribute('draggable')!='false'))
		{
			html_node.parentNode.style.position='absolute';
		}*/

		html_node.style.display='none';

		img.src='{$IMG,treefield/plus}'.replace(/^http:/,window.location.protocol);
		img.title=img.title.replace('{!CONTRACT_TREE_LIST^;}','{!EXPAND_TREE_LIST^;}');
		img.alt=img.alt.replace('{!CONTRACT_TREE_LIST^;}','{!EXPAND_TREE_LIST^;}');
	}

	fixUpNodePositions(this.object.name);

	trigger_resize();

	this.object.busy=false;

	return true;
}

tree_list.prototype.handle_selection=function(event,assume_ctrl) // Not called as a method
{
	if (typeof event=='undefined') var event=window.event;
	if (typeof assume_ctrl=='undefined') var assume_ctrl=false;

	var element=document.getElementById(this.object.name);
	if (element.disabled) return;
	var i;
	var selected_start=(element.value=='')?[]:(this.object.multi_selection?element.value.split(','):[element.value]);
	if ((!assume_ctrl) && (event.shiftKey) && (this.object.multi_selection))
	{
		cancelBubbling(event);
		if (typeof event.preventDefault!='undefined') event.preventDefault();

		// We're holding down shift so we need to force selection of everything bounded between our last click spot and here
		var all_a=document.getElementById('tree_list__root_tree_list').getElementsByTagName('label');
		var pos_last=-1;
		var pos_us=-1;
		if (this.object.last_clicked==null) this.object.last_clicked=all_a[0];
		for (i=0;i<all_a.length;i++)
		{
			if (all_a[i]==this || all_a[i]==this.parentNode) pos_us=i;
			if (all_a[i]==this.object.last_clicked || all_a[i]==this.object.last_clicked.parentNode) pos_last=i;
		}
		if (pos_us<pos_last) // ReOrder them
		{
			var temp=pos_us;
			pos_us=pos_last;
			pos_last=temp;
		}
		var that_selected_id,that_xml_node,that_type;
		for (i=0;i<all_a.length;i++)
		{
			that_type=this.getAttribute('id').charAt(5+this.object.name.length);
			if (that_type=='r') that_type='c';
			if (that_type=='s') that_type='e';

			that_selected_id=(this.object.use_server_id)?all_a[i].getAttribute('serverid'):all_a[i].getAttribute('id').substr(7+this.object.name.length);
			that_xml_node=this.object.getElementByIdHack(that_selected_id,that_type);
			if (that_xml_node.getAttribute('selectable')=='true' || this.object.all_nodes_selectable)
			{
				if ((i>=pos_last) && (i<=pos_us))
				{
					if (!selected_start.inArray(that_selected_id))
						all_a[i].handle_selection(event,true);
				} else
				{
					if (selected_start.inArray(that_selected_id))
						all_a[i].handle_selection(event,true);
				}
			}
		}

		return;
	}
	var type=this.getAttribute('id').charAt(5+this.object.name.length);
	if (type=='r') type='c';
	if (type=='s') type='e';
	var real_selected_id=this.getAttribute('id').substr(7+this.object.name.length);
	var xml_node=this.object.getElementByIdHack(real_selected_id,type);
	var selected_id=(this.object.use_server_id)?xml_node.getAttribute('serverid'):real_selected_id;

	if (xml_node.getAttribute('selectable')=='true' || this.object.all_nodes_selectable)
	{
		var selected_end=selected_start;
		for (i=0;i<selected_start.length;i++)
		{
			this.object.make_element_look_selected(document.getElementById(this.object.name+'tsel_'+type+'_'+selected_start[i]),false);
		}
		if ((!this.object.multi_selection) || (((!event.ctrlKey) && (!event.metaKey) && (!event.altKey)) && (!assume_ctrl)))
		{
			selected_end=[];
		}
		if ((selected_start.inArray(selected_id)) && (((selected_start.length==1) && (selected_start[0]!=selected_id)) || ((event.ctrlKey) || (event.metaKey) || (event.altKey)) || (assume_ctrl)))
		{
			selected_end.arrayDelete(selected_id);
		} else
		if (!selected_end.inArray(selected_id))
		{
			selected_end.push(selected_id);
			if (!this.object.multi_selection) // This is a bit of a hack to make selection look nice, even though we aren't storing natural IDs of what is selected
			{
				var anchors=document.getElementById('tree_list__root_'+this.object.name).getElementsByTagName('label');
				for (i=0;i<anchors.length;i++)
				{
					this.object.make_element_look_selected(anchors[i],false);
				}
				this.object.make_element_look_selected(document.getElementById(this.object.name+'tsel_'+type+'_'+real_selected_id),true);
			}
		}
		for (i=0;i<selected_end.length;i++)
		{
			this.object.make_element_look_selected(document.getElementById(this.object.name+'tsel_'+type+'_'+selected_end[i]),true);
		}

		element.value=selected_end.join(',');
		element.selected_title=(selected_end.length==1)?xml_node.getAttribute('title'):element.value;
		element.selected_editlink=xml_node.getAttribute('edit');
		if (element.value=='') element.selected_title='';
		if (element.onchange) element.onchange();
		if (typeof element.fakeonchange!='undefined' && element.fakeonchange) element.fakeonchange();
	}

	if (/*(!event.ctrlKey) && */(!assume_ctrl)) this.object.last_clicked=this;

	return;
}

tree_list.prototype.make_element_look_selected=function(target,selected)
{
	if (!target) return;
	if (!selected)
	{
		target.setAttribute('style','background-color: white; color: black');
		target.style.backgroundColor='white';
		target.style.color='black';
	} else
	{
		target.setAttribute('style','background-color: #316AC5; color: white');
		target.style.backgroundColor='#316AC5';
		target.style.color='white';
	}
	target.style.cursor='pointer';
}


