var XTRxlist = Class.create();

XTRxlist.prototype = {
	/* source div */
	initialize : function(source, _obj, options, mode) {
		this.source = $(source);

		/*
		 * connection object _obj must have function load_xlist_data(anc_id) or
		 * another defined in options function
		 */

		this._obj = _obj;
		this.rows_in_table = 0;
		this.current_anc_id = 0;
		this.set_options(options);
		this.build_up_table();
		this.mode = mode;
        this.current_page=1;

		if (mode == 'table') {
            this.pagination=new Element('div',{className:'pagination'}); 
			this.initialize_table();
			if(this.get_data(this.options.startWithAncestor, 0))
            {
                this.source.appendChild(this._build_up_table);
          //TableKit.Resizable.init(this._build_up_table);
                //Sortable.create(this.tbody, { tag:"tr", ghosting:true });

                this.source.appendChild(this.pagination);  
            }
		} else {

			this.initialize_dialog();
			this.get_data(this.options.startWithAncestor);

		}
	}

	,
	set_options : function(options) {
		this.cache = new Hash();
		this.options = {};
		this.imageCache = new Hash();
		this.column_order = new Array();

		/*
		 * complex {Hash} colunmnName=> [className]=''
		 * [source]='sourceColumn->id' [actionVars]=hash[var1][var2] //если поле
		 * [virtual]=true [backFunc]={onclick_function}
		 * 
		 */

		this.options.complex = new Hash();

		/* Hash colunmnName=>colunmnviewName */
		this.options.columnsHeaders = null;
		// данна€ настройка выполн€ет оба действи€ выбор пункта и считывание
		// низлежащи нод
		this.options.permanent = false;
        
        this.options.showControls =true; 
		/* результат пишем в input с id из this.options.resultSource */
		this.options.resultSource = null
		/* результат ID пишем в input с id из this.options.resultIDSource */
		this.options.resultIDSource = null
    
		this.options.resultFunc = null;

		this.last_selected = null;

		this.options.columnsHeadersWidth = null;
		this.options.tableId = 'xlisttable';
		this.options.className = 'xlist-table';
		/* данна€ настройка только дл€ дерева */
		this.options.startWithAncestor = 1;
        
        this.options.rows_per_page=0;
		/* инетепретировать как объект типа image=>'IMAGE' */
		this.options.columnsInterpretAs = null;
		// this.options.selectable=null;
		this.options.ancestors = null;
        this.options.include_root_in_selection=true;
		this.options.select_param_to_input = 'name';
		this.options.serverCallFunc = 'load_xlist_data';

		Object.extend(this.options, options || {});
		if ((typeof this.options.images) != 'undefined')
			this.create_image_cache();
	},
	// картинки записываютс€ и вызываютсс€ согласно алиасам
	// mypic=>'images/picture'
	create_image_cache : function() {
		this.options.images.each(function(pair) {
					var img = new Image();
					img.src = pair.value;
					this.imageCache.set(pair.key,img);

				}.bind(this));

	},
	/*
	 * подключаем pop
	 */
	connectXpop : function(xpop_source) {
		this.options.xpop = xpop_source;
	},

	build_up_table : function() {

		this._build_up_table = new Element('table', {
			className : this.options.className,
			id : this.options.tableId,
			width : '100%',
			cellpadding : '0',
			cellspacing : '0',
			border : '0'
		});

		

	},

	initialize_table : function() {

		thead = new Element('thead');
		var tr = new Element('tr');
		i = 0;
		this.options.columnsHeaders.each(function(pair) {
			td = new Element('th');
            strong=new Element('strong'); strong.appendChild(document.createTextNode(pair.value));            
            td.appendChild(strong);
			td.width = this.options.columnsHeadersWidth[i];
			this.column_order[i++] = pair.key;
			tr.appendChild(td);
		}.bind(this));

		thead.appendChild(tr);
		this._build_up_table.appendChild(thead);

		this.tbody = new Element('tbody');
		this._build_up_table.appendChild(this.tbody);
        
        
        

	},

	initialize_dialog : function() {

		// main table
		this.build_up_table();

		// header_table
		htable = new Element('table', {
			className : this.options.className + '-outer',
			id : this.options.tableId,
			width : '100%',
			cellpadding : '2',
			cellspacing : '2',
			border : '0'
		});
		htbody = new Element('tbody');

		thead = new Element('thead');
		var tr = new Element('tr', {className : 'xlistheader'});
		i = 0;
		this.options.columnsHeaders.each(function(pair) {
			td = new Element('th');
            strong=new Element('strong').appendChild(document.createTextNode(pair.value));
            td.appendChild(strong);
			td.width = this.options.columnsHeadersWidth[i];
			this.column_order[i++] = pair.key;
			tr.appendChild(td);

		}.bind(this));

		thead.appendChild(tr);
		htable.appendChild(thead);

		htable.appendChild(htbody);

		this.tbody = new Element('tbody');
		this._build_up_table.appendChild(this.tbody);

		/* добавл€ем к основному div */
		cdiv = new Element('div', {className : 'xlist-inner'});
		cdiv.appendChild(this._build_up_table);

		/* control section */
        if(this.options.showControls)
        {
		    control_div = new Element('div', {className : 'control'});
        }
		
        up_button = new Element('div', {className : 'upbutton'});
        up_button.appendChild(new Element('a', {href : 'javascript:void(0)'}));

		up_button.onclick = this.on_up_click.bindAsEventListener(this);
		control_div.appendChild(up_button);

		/* result_div section */
		result_div = new Element('div', {className : 'result-control'});

		this.result_input = new Element('input', {type : 'text'});

		result_ok_button = new Element('div', {className : 'xbutton'});
        a=new Element('a');a.appendChild(document.createTextNode(_lang_xlist['choose']));
        result_ok_button.appendChild(a);
		
        result_ok_button.onclick = this.on_ok_click.bindAsEventListener(this);
		result_cancel_button = new Element('div', {className : 'xbutton'});
        a=new Element('a');
        a.appendChild(document.createTextNode(_lang_xlist['cancel']));
        result_cancel_button.appendChild(a);
        
        
		result_cancel_button.onclick = this.on_cancel_click.bindAsEventListener(this);

		result_div.appendChild(this.result_input);
		result_div.appendChild(result_ok_button);
		result_div.appendChild(result_cancel_button);

		// building finish
		this.source.appendChild(control_div);
		this.source.appendChild(htable);
		this.source.appendChild(cdiv);
		this.source.appendChild(result_div);
        
        

	},

	on_cancel_click : function() {
		this.options.xpop.hideXpop();
	},

	/* data_set[row_num]=array(name=>'value',name=>'value') */

	set_enumeration : function(data_chunk) {
		var new_order = new Array();

		data_chunk.each(function(pair) {

			key = this.column_order.indexOf(pair.key);
			if (key != -1) {
				new_order[key] = pair.value;
			}
		}.bind(this));

		return new_order;
	},

	on_ok_click : function() {
		// передаем параметры

		if (Object.isFunction(this.options.resultFunc)) {
			this.options.resultFunc(this.result_input.value, this.last_selected);

		} else {
			$(this.options.resultSource).value = this.result_input.value;
			$(this.options.resultIDSource).value = this.last_selected;
		}

		// закрываем окно
		this.options.xpop.hideXpop();
	},

	on_up_click : function(event) {
		this.get_data(this.options.startWithAncestor);
	},

	path_name : function(id,cpath) 
    {        
       
        if(!cpath)cpath=new Array();                 
            cpath.push(id);        
        //поиск предка        
            if(!Object.isUndefined(this.cache))
            {
            
            ks=this.cache.keys();
            for(j=0;j<=ks.length;j++){
            
                        if(!Object.isUndefined(this.cache.get(ks[j]).get(id)))
                        {
                                    if(ks[j]==this.options.startWithAncestor)
                                    {       
                                            if(this.options.include_root_in_selection)
                                            {
                                                cpath.push(ks[j]);                         
                                            }            
                                            cpath.reverse();
                                            vr='';
                                            for(i=0;i<cpath.length-1;i++)
                                            {
                                              if(cpath.length-2==i){delim='';}else{delim='/';}
				                              if(this.cache.get(cpath[i]).get(cpath[i+1])!=null)
                                              {
                                                vr+=''+this.cache.get(cpath[i]).get(cpath[i+1])[this.options.select_param_to_input]+delim;
                                              }
                                            
                                            }
                                            return vr;
                                    
                                    }
                                    else{
                                       return  this.path_name(ks[j],cpath);
                                    }
                        
                        }
            }            
            
     
            }   
        return '';
	},
    
	dblclick : function(event) {
		this.onrowclick(event);
		this.on_ok_click();

	},
	onrowclick : function(event) {
		var elt = Event.element(event);
		if (typeof elt != 'undefined') {
			up_id = elt.up('tr').id;
			if ((!Object.isUndefined(this.cache.get(this.current_anc_id).get(up_id)))) {
				if (Object.isNumber(this.cache.get(this.current_anc_id).get(up_id)['_S_'])) {
					this.last_selected = up_id;
                    
                    this.result_input.value=this.path_name(up_id);					
                  //this.result_input.value =+this.cache[this.current_anc_id][up_id][this.options.select_param_to_input];

					if (this.options.permanent
							&& !(Object.isNumber(this.cache.get(this.current_anc_id).get(up_id)['_E_']))) {						
                                       
                            this.get_data(up_id);
                        
					}

				} else {
					                  
                    this.get_data(up_id);
                       this.result_input.value=this.path_name(up_id);    
           
				}

			} else {
      
				this.get_data(up_id);
                

			}
		}

	},

	update_table_list : function(data_set) {

		if (this.rows_in_table > 0)
			this.tbody.getElementsBySelector('tr').invoke('remove');
		/* установка нумерации */
		i = 0;
		var tr = null;        
		data_set.each(function(pair) {
                    
            i++;
			pval = $H(pair.value)
            
             
             if(!Object.isUndefined(pval.get('id')))
             {
             tr = new Element('tr').__extend({rowid :pval.get('id')});
             }else{
             tr = new Element('tr');             
             }   

			j = 0;

			this.options.columnsHeaders.keys().each(function(val) {

				value = pval.get(val);
				if (!Object.isUndefined(this.options.complex.get(val))) {
					
                    cmpx = this.options.complex.get(val);

					if ((!Object.isUndefined(cmpx['source']))
							&& (cmpx['virtual'])) 
                            {
						value = '';
					}

					if ((!Object.isUndefined(cmpx['source']))
							&& (cmpx['source'])) {
						h = new Hash();
						
                        h.set(cmpx['source'], pval.get(cmpx['source']));

						(Object.isUndefined(cmpx['actionVars']))
						{
							cmpx.actionVars = new Hash();
						}
						cmpx.actionVars.update(h);
					}
				}

                if (!Object.isUndefined(cmpx['booltype']))
                {
                    if(pval.get(cmpx['source'])=='1')
                    {
                        cmpx.className=cmpx.className+' '+cmpx.activestate;
                    
                    }else{
                        cmpx.className=cmpx.className+' '+cmpx.nonactivestate;
                        
                    }
                }
                
              if (!Object.isUndefined(cmpx['transform']))
               
                {
                                    
                  value=cmpx['transform'][pval.get(cmpx['source'])]
                    
                }
                
                if (!Object.isUndefined(cmpx['div'])){
                node = new Element('div', {
                    className : cmpx.className}).__extend({
                    params : cmpx.actionVars});
                    node.innerHTML=value;
                
                }else{
				    node = new Element('a', {
					className : cmpx.className}).__extend({
					params : cmpx.actionVars});
                    node.appendChild(document.createTextNode(value));                                        
				    node.href = 'javascript:void(0)';
                }

				if (Object.isFunction(cmpx.backFunc)) {
					node.onclick = cmpx.backFunc;

				}

				td = new Element('td');td.appendChild(node);                
				tr.appendChild(td);
				j++;

			}.bind(this));

			this.tbody.appendChild(tr);
			this.rows_in_table = i;
		}.bind(this)

		);
        /*строим постраничный переход*/
      
          this.pagination.update();
          if(this.pages_num>1)
          {
              for(i=1;i<this.pages_num+1;i++)
              {
                 className=(i==this.current_page)?'selected':'';
                 a=new Element('a',{className:className}).observe('click', this.pagination_click.bind(this));
                 a.appendChild(document.createTextNode(i));
                 this.pagination.appendChild(a);
              } 
          }

	},

    pagination_click:function(evt)
    {
        var elt = Event.element(evt);
        this.current_page=elt.firstChild.textContent;
        
        this.get_data(this.current_anc_id,(parseInt(elt.firstChild.textContent)-1)*this.options.rows_per_page);
    }
    ,
    
	update_list : function(data_set) {
		/* очищаем */
		ds=$H(data_set);
        if (this.rows_in_table > 0)
			this.tbody.getElementsBySelector('tr').invoke('remove');
		/* установка нумерации */
		i = 0;
		var tr = null;
		ds.each(function(pair) {
			tr = document.createElement('tr');
			i++;
			tr.onclick = this.onrowclick.bindAsEventListener(this);
			tr.id = pair.key;
			pval = $H(pair.value)
			_enumeration = this.set_enumeration(pval);
			j = 0;
			_enumeration.each(function(val) {
				// комбинции с интерпретатором представлени€

				_val = val;
				if (typeof this.options.columnsInterpretAs != 'undefined') {
					column_name = this.column_order[j];

					switch (this.options.columnsInterpretAs.get(column_name)) {
						case 'IMAGE' :
							if (typeof this.imageCache.get(val) != 'undefined') {
								_val = new Element('img');
								_val.src = this.imageCache.get(val).src;
								break;
							}
					}
				 
                }
                if(typeof _val=='object') {
                    td = new Element('td');
                    td.appendChild(_val);
                }else{td = new Element('td');
                    td.appendChild(document.createTextNode(_val));
                }

				td.width = this.options.columnsHeadersWidth[j];

				tr.appendChild(td);
				j++;

			}.bind(this));

			tr.onclick = this.onrowclick.bindAsEventListener(this);
			tr.ondblclick = this.dblclick.bindAsEventListener(this);

			this.tbody.appendChild(tr);

			this.rows_in_table = i;
		}.bind(this));

	},

	// get_data вызвывает update_list по получению данных
	get_data : function(id, _startRow) {
    
		if (typeof _startRow == null) {
			_page = 0;
		}

		if (!Object.isUndefined(this.cache.get(id))) {
			view = this.cache.get(id);
			this.current_anc_id = id;
		} else {

			actionList=new Array();
			this.current_anc_id = id;
			actionList[this.options.serverCallFunc] = {
				anc_id : id,
				startRow : _startRow,
                rows_per_page:this.options.rows_per_page
			};
			this._obj.execute(actionList);
            view=null;
			
            if(this._obj.result)
            {
                if (this.mode == "table")
                {
				    view = $H(this._obj.result['data_set'])
                    this.pages_num=this._obj.result['pages_num'];
			    } else {
				    this.obj_types = $H(this._obj.result['obj_types']);
				    this.cache.set(id,$H(this._obj.result['data_set']));
				    view = this.cache.get(id);
			    }
            }
		}

		if (view) {
			this.ancestor = id;
			if (this.mode == "table") {
				this.update_table_list(view);
			} else {
				this.update_list(view);
			}
            return true;    
		}

	}

}