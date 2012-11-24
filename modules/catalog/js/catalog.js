var XTRobjconnected = Class.create();
var Xpop, Xpop2;
XTRobjconnected.prototype = {

    initialize: function (source, options) {
        this.objects = new Hash();

        this.source = new Element('ul');
        this.source.id = 'cobjlist';
        $(source).appendChild(this.source);

    },

    add_objc: function (params) {

        _id = params.id;
        id = 'cj_' + _id;

        if (this.objects.keys().indexOf(id) != -1) {
            alert(_lang_catalog['such_element_already_is_present']);
            return;
        }

        li = new Element('li');
        li.update('<span>' + params.ConName + '</span>');
        li.id = id;

        this.objects.set(id, {
            id: _id,
            _source: li,
            params: params
        });

        this.source.appendChild(li);

        but = new Element('a', {
            className: "button small iclear",
            href: "javascript:void(0)"
        }).__extend({
            _id: id
        }).observe('click', this.del.bind(this));
        li.appendChild(but);
        but.update('<span></span>&nbsp');
        this.created = true;
        Sortable.destroy(this.source);
        Sortable.create(this.source, {
            dropOnEmpty: true
        });

    },

    get_objcs: function () {

        if (this.created) {
            //Sortable.serialize
            slo = Sortable.sequence('cobjlist');
            rpr = new Array();
            for (i = 0; i < slo.size(); i++) {
                delete this.objects.get('cj_' + slo[i])._source;
                rpr.push(this.objects.get('cj_' + slo[i]));
            }
            return rpr;
        }


    },

    del: function (elt) {

        elt = Event.element(elt).up('li');

        this.objects.get(elt.id)._source.remove();
        this.objects.unset(elt.id);

    }


}

var XTRcateditor = Class.create();
XTRcateditor.prototype = {
    initialize: function (source, alters) {
        source = $(source);
        this.current = null;
        this.sforms = null;
        this.faqs = null;
        this.subgroups = new Hash();
        this.properties = new Hash();
        this.selector = new Hash();
        this.connector = new Connector('catalog');
        this.objectsTypeList = $H({
            FILE: _lang_catalog['file'],
            IMAGE: _lang_catalog['image'],
            FIELD: _lang_catalog['field'],
            ICURRENCY: _lang_catalog['ishop_currency'],
            SELECTOR: _lang_catalog['selector'],
            LONGFIELD: _lang_catalog['text'],
            CURRENCY: _lang_catalog['currency'],
            IFOLDER: _lang_catalog['folder_with_images'],
            DATE: _lang_common['date'],
            BOOL: _lang_common['boolean'],
            CATOBJ: _lang_catalog['object_catalog'],
            FUSER: _lang_catalog['object_fuser'],
            ALBUM: _lang_catalog['object_album'],
            DOCS: _lang_catalog['object_docs'],
            FAQ: _lang_catalog['object_faq'],
            SFORM: _lang_catalog['object_sform']
        });
        this.options = new Array();
        this.options.ClassName = 'prop-container';
        if (!alters) {
            this.source = new Element('ul');

            this.subgroups_source = new Element('ul');
            this.subgroups_source.id = 'subgroups';

            this.source.id = 'nidlist';

            source.update('<ul class="prop-title"><li><div>' + _lang_common['name'] + '</div><div>' + _lang_common['alias'] + '</div></li></ul>');
            source.appendChild(this.source);
            source.appendChild(this.subgroups_source);

        } else {

            this.source = source;
        }
    },


    group_dialog: function () {

        columns = $H({
            image: ' ',
            name: _lang_common['name']
        });
        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H({
            image: 'IMAGE'
        });
        _images = $H({
            group: 'xres/ximg/tree/folderClosed.gif',
            page: 'xres/ximg/tree/page.gif'
        });

        if (Object.isUndefined(arguments[0])) {
            xlist_name = "xlist";
        } else {
            xlist_name = arguments[0];
        }
        if (Object.isUndefined(arguments[1])) {
            fcall = 'load_xlist_fuser';
        } else {
            fcall = arguments[1];
        }
        if (Object.isUndefined(arguments[2])) {
            startwith = 1
        } else {
            startwith = arguments[2];
        }
        if (Object.isUndefined(arguments[3])) {
            anobj = 'fuser'
        } else {
            anobj = arguments[3];
        }
        if (Object.isUndefined(arguments[4])) {
            anobj_id = 'fuser_id'
        } else {
            anobj_id = arguments[4];
        }
        if (Object.isUndefined(arguments[5])) {
            dial = 'dialogtable'
        } else {
            dial = arguments[5];
        }

        return xlist = new XTRxlist(xlist_name, this.connector, {
            permanent: true,
            resultSource: anobj,
            serverCallFunc: fcall,
            resultIDSource: anobj_id,
            columnsHeaders: columns,
            tableId: dial,
            startWithAncestor: startwith,
            columnsHeadersWidth: _columnsHeadersWidth,
            columnsInterpretAs: _columnsInterpretAs,
            images: _images,
            className: 'dialog-table',
            include_root_in_selection: true,
            usebackoff: 1
        });
    },


    add_selector_option: function () {
        s = this.selector.get('selector');
        for (i = 0; i < s.options.length; i++) {
            if (this.selector.get('optValue').value == s.options[i].value) {
                alert(_lang_catalog['option_with_such_name_already_exists']);
                return;
            }

        };


        s.options[s.options.length] = new Option(this.selector.get('optName').value, this.selector.get('optValue').value);


    },

    select_selector_option: function () {
        s = this.selector.get('selector');
        this.selector.set('current_selected', s.selectedIndex);
        this.selector.get('updateb').disabled = false;
        this.selector.get('optValue').value = s.options[this.selector.get('selector').selectedIndex].value;
        this.selector.get('optName').value = s.options[this.selector.get('selector').selectedIndex].text;
    },

    update_selector_option: function () {


        this.selector.get('selector').options[this.selector.get('current_selected')].value = this.selector.get('optValue').value;
        this.selector.get('selector').options[this.selector.get('current_selected')].text = this.selector.get('optName').value;
        this.selector.get('updateb').disabled = true;
        this.selector.set('current_selected', null);

    },

    del_selector_option: function () {
        s = this.selector.get('selector');
        for (i = 0; i < s.length; i++) {
            if (s.options[i].selected) {
                s.remove(i);
            }
        }

    },

    //subgroup
    add_property_subgroup: function () {
        this.prop_editor_win = XTR_main.dhxWins.createWindow("prop_editor", 20, 10, 600, 630);
        this.prop_editor_win.centerOnScreen();
        this.prop_editor_win.setText(_lang_catalog['edit_property']);
        this.prop_editor_win.attachHTMLString(XTR_main.get_tpl('catalog', 'catalog_add_subprop', true));

    },


    save_subgroup: function (item, create_sortable, donotclose) {

        if (!this.current_sub) {
            li = new Element('li');
            if (item == null) {
                item = {
                    SubName: $('SubName').value,
                    SubNameEng: $('SubNameEng').value
                };
                id = getContinua();
                id = 'i_' + Math.abs(id);
            } else {
                id = 'i_' + item.id;
            }

            li.update('<div >' + item.SubName + '</div><div>' + item.SubNameEng + '</div><div class="edit-prop"></div><div class="del-prop"></div>');
            li.id = id;
            this.subgroups.set(id, item);
            this.subgroups_source.appendChild(li);

            if (create_sortable) {
                Sortable.create(this.subgroups_source, {
                    dropOnEmpty: true
                });
            }
            this.subgroups.get(id)._source = li;
            a = (new Element('a', {
                className: "button small",
                href: "javascript:void(0)"
            }).__extend({
                _id: id
            }).observe('click', this.edit_subgroup.bind(this)));
            a.insert(_lang_common['edit']);

            a2 = (new Element('a', {
                className: "button small iclear",
                href: "javascript:void(0)"
            }).__extend({
                _id: id
            }).observe('click', this.del_subgroup.bind(this)));
            a2.insert('<span></span>&nbsp;');

            li.down(1).next(0).appendChild(a);
            li.down(1).next(1).appendChild(a2);
        } else {

            item = $H({
                SubName: $('SubName').value,
                SubNameEng: $('SubNameEng').value
            });
            li = this.subgroups.get(this.current_sub)._source;
            this.subgroups.get(this.current_sub) = item;
            li.down().update(item.SubName);
            li.down().next(0).update(item.SubNameEng);
            this.subgroups.get(this.current_sub)._source = li;
            this.current_sub = null;

        }
        if (!donotclose) {
            this.prop_editor_win.close();
        }


    },






    create_SELECTOR: function (options) {

        this.selector.set('selector', new Element('select', {
            id: 'catselector',
            className: 'mult-selector',
            name: 'catselector',
            size: 10
        }));

        s = this.selector.get('selector');
        s.setAttribute('multiple', true);
        s.setAttribute('fullselect', true);

        if (!Object.isUndefined(options)) {
            this.attach_selector_options(s, options, false)
        }

        s.onchange = this.select_selector_option.bind(this);

        $('property_type_editor').appendChild(s);


        this.selector.set('optName', new Element('input', {
            Name: "optionName"
        }));
        $('property_type_editor').appendChild(this.selector.get('optName'));
        this.selector.set('optValue', Element('input', {
            Name: "optionValue"
        }));
        $('property_type_editor').appendChild(this.selector.get('optValue'));

        bt = new Element('button', {});
        bt.observe('click', this.add_selector_option.bind(this));
        bt.appendChild(document.createTextNode(_lang_common['add']));
        $('property_type_editor').appendChild(bt);

        bt_save = new Element('button', {
            disabled: true
        });
        bt_save.observe('click', this.update_selector_option.bind(this));
        bt_save.appendChild(document.createTextNode(_lang_common['save']));

        $('property_type_editor').appendChild(bt_save);

        this.selector.set('updateb', bt_save);
        bt_del = new Element('button', {});
        bt_del.observe('click', this.del_selector_option.bind(this));
        bt_del.appendChild(document.createTextNode(_lang_common['delete']));
        $('property_type_editor').appendChild(bt_del);


    },
/*
    create_FUSER:function(options){
               
               //debugger;
               this.selector.set('selector_fuser',new Element('input',{id:'fuser', name:'fuser', type : 'text', style : 'width:100px;'}));
               this.selector.set('selector_fuser_id',new Element('input',{id:'fuser_id', name:'fuser_id', type : 'hidden'}));
               s1=this.selector.get('selector_fuser');
               s2=this.selector.get('selector_fuser_id');

              $('property_type_editor').appendChild(s1);
              $('property_type_editor').appendChild(s2);

               bt=new Element('a',{style: 'width : 183px;', className : 'button small', id: 'startXlist_fuser', href : 'javascript:void(0);'});              
               bt.observe('click','javascript:void(0);');
               bt.appendChild(document.createTextNode(_lang_common['select']));
               $('property_type_editor').appendChild(bt);               
                  
               s3=this.selector.get('selector_fuser_button');
               var Xpop = new XTRpop('startXlist_fuser', null, { position: 'bottom', delta_x: -203, delta_y: 0 });
               xlist = this.group_dialog(Xpop.tool_tip.id,'load_xlist_fuser',1, 'fuser', 'fuser_id','dialogtable');
               xlist.connectXpop(Xpop);
}, 
*/



    change_type: function () {
        d = $('property_type').firstChild;
        option = d.options[d.selectedIndex];
        $('property_type_editor').update();

        switch (option.value) {
        case 'SELECTOR':

            this.create_SELECTOR();
            break;

        case 'CURRENCY':

            this.create_SELECTOR();

            break;
        }

    },

    attach_selector_options: function (sel_source, hash, selected) {
        hash.each(function (pair) {
            if (pair.key == selected) {
                sel = true;
            } else {
                sel = false;
            }
            opt = document.createElement("option");
            opt.value = pair.key;
            opt.text = pair.value;
            opt.selected = sel;
            sel_source.appendChild(opt);
        });
    },
    //m
    createTypeSelector: function (selected) {
        select = new Element('select', {
            className: 'inp i314',
            name: 'Type'
        });
        this.attach_selector_options(select, this.objectsTypeList, selected)
        select.observe('change', this.change_type.bind(this));
        return select;
    },



    set_options: function (opt_array) {
        Object.extend(this.options, opt_array || {});
    },



    get_properties_data: function () {

        if (!Object.isUndefined(this.properties)) {
            //Sortable.serialize
            slo = Sortable.sequence('nidlist');
            rpr = new Array();
            for (i = 0; i < slo.size(); i++) {
                c = this.properties.get('i_' + slo[i]);
                s = Object.clone(c);

                delete s._source;
                rpr.push(s);

            }
        }
        return rpr;

    },

    get_subgroups_data: function () {

        if (!Object.isUndefined(this.subgroups)) {
            //Sortable.serialize
            slo = Sortable.sequence('subgroups');
            rpr = new Array();
            for (i = 0; i < slo.size(); i++) {

                c = this.subgroups.get('i_' + slo[i]);
                delete c._source;
                c['id'] = 'i_' + slo[i];
                rpr.push(c);

            }
        }
        return rpr;

    },


    create_property_code: function (params) {
        if (!Object.isUndefined(params)) {
            switch (params.Type) {
            case 'LONGFIELD':
                return '<p>' + params.Alias + ':</p><textarea ondblclick=XTR_main.apply_weditor(this)  name="' + params.Name + '" id="' + params.Name + '"></textarea>';

                break;
            case 'DATE':
                return '<p>' + params.Alias + _lang_catalog['date_dd_mm_gggg'] + '</p> <input id="' + params.Name + '"  name="' + params.Name + '" type="text" />';
                break;

            case 'ICURRENCY':
            case 'FIELD':
            case 'CURRENCY':
                return '<p>' + params.Alias + '</p> <input id="' + params.Name + '" name="' + params.Name + '" type="text" />';
                break;

            case 'CATOBJ':
                return '<p>' + params.Alias + '</p><input id="' + params.Name + 'Alias" class="inp-b2 required" readonly /><input id="' + params.Name + '" type="hidden"  />' + '<a class="button small" onclick="XTR_catalog.show_catobj_list(this,' + "'" + params.Name + "'" + ')" href="javascript:void(0)" >' + _lang_common['select'] + '</a><a class="button small iclear" href="javascript:void(0)" onclick="javascript:$(' + "'" + params.Name + "Alias'" + ').value=' + "''" + ';$(' + "'" + params.Name + "'" + ').value=' + "''" + '"><span></span>&nbsp;</a>';

                break;

            case 'SELECTOR':

                if (!Object.isUndefined(params.catselector)) {
                    cs = arr_convert_to_hash(params.catselector)
                    optcode = '';
                    cs.each(function (opt) {
                        selected = opt[0] == '-' ? 'selected' : '';
                        optcode += '<option ' + selected + ' value="' + opt[0] + '">' + opt[1] + '</option>';
                    })
                }
                return '<p>' + params.Alias + ':</p> <select id="' + params.Name + '"  name="' + params.Name + '">' + optcode + '</select>';
                break;

            case 'IFOLDER':
                return '<p>' + params.Alias + '(' + _lang_catalog['folder_with_images'] + ')</p><input  class="inp-b2" name="' + params.Name + '" id="' + params.Name + '"  readonly /><a class="button small" href="javascript:void(0)" onclick="xFileManager.selectFolder(' + "'" + params.Name + "'" + ')">' + _lang_common['select'] + '</a><a class="button small iclear" href="javascript:void(0)" onclick="javascript:$(' + "'" + params.Name + "'" + ').value=' + "''" + '"><span></span>&nbsp;</a>';
                break;

            case 'BOOL':
                return '<ul class="chk"><li><input type="checkbox" name="' + params.Name + '"/>' + params.Alias + '</li></ul>';
                break;

            case 'IMAGE':
                return '<p>' + params.Alias + '</p><input name="' + params.Name + '" id="' + params.Name + '"  class="inp-b2" /><a class="button small" href="javascript:void(0)" onclick="xFileManager.selectImage(' + "'" + params.Name + "'" + ')">' + _lang_common['select'] + '</a><a class="button small iclear" href="javascript:void(0)" onclick="javascript:$(' + "'" + params.Name + "'" + ').value=' + "''" + '"><span></span>&nbsp;</a>';
                break;


            case 'FILE':
                return '<p>' + params.Alias + '</p><input name="' + params.Name + '" id="' + params.Name + '"  class="inp-b2"/><a class="button small" href="javascript:void(0)" onclick="xFileManager.selectFile(' + "'" + params.Name + "'" + ')">' + _lang_common['select'] + '</a>' + '<a class="button small iclear" href="javascript:void(0)" onclick="javascript:$(' + "'" + params.Name + "'" + ').value=' + "''" + '"><span></span>&nbsp;</a>';

                break;

            case 'FUSER':
            case 'ALBUM':
            case 'DOCS':
            case 'FAQ':
                return '<p>' + params.Alias + '</p><input id="' + params.Name + '_name" name="' + params.Name + '_name" class="inp-b1" readonly /><input id="' + params.Name + '" name="' + params.Name + '" type="hidden" value="1"/><a class="button small" href="javascript:void(0)" id="startXlist_' + params.Name + '">' + _lang_common['select'] + '</a>';
                break;
            case 'SFORM':

                if (!this.sforms) {
                    this.connector.execute({
                        load_sforms: {}
                    });
                    this.sforms = this.connector.result.sforms;
                }

                var optcode = '<option value=""></option>';

                this.sforms.each(function (obj) {
                    selected = '';
                    optcode += '<option ' + selected + ' value="' + obj.id + '">' + obj.Name + '</option>';
                })

                return '<p>' + params.Alias + ':</p> <select id="' + params.Name + '"  name="' + params.Name + '">' + optcode + '</select>';
                break;

/*           case 'FAQ' :
                
                if (!this.faqs) {
                    this.connector.execute({load_faqs :{}});
                    this.faqs=this.connector.result.faqs;
                }
                
                var optcode ='<option value=""></option>';
                
                this.faqs.each(function(obj){
                    selected = '';
                    optcode+='<option '+selected+' value="'+obj.id+'">'+obj.basic+'</option>';
                })
               
               return '<p>'+params.Alias+':</p> <select id="'+params.Name+'"  name="'+params.Name+'">'+optcode+'</select>';
               break;
*/



            }
        }
    },

    create_property_binds: function (params) {

        if (!Object.isUndefined(params)) {
            switch (params.Type) {

            case 'FUSER':
                var Xpop = new XTRpop('startXlist_' + params.Name, null, {
                    position: 'bottom',
                    delta_x: -203,
                    delta_y: 0,
                    className: 'dialogtable02',
                    donotdestroy: true
                });
                var xlist = this.group_dialog(Xpop.tool_tip.id, 'load_xlist_fuser', 1, params.Name + '_name', params.Name, 'dialogtable2');
                xlist.connectXpop(Xpop);
                break;
            case 'ALBUM':
                var Xpop2 = new XTRpop('startXlist_' + params.Name, null, {
                    position: 'bottom',
                    delta_x: -203,
                    delta_y: 0,
                    donotdestroy: true
                });
                var xlist2 = this.group_dialog(Xpop2.tool_tip.id, 'load_xlist_gallery_albums', 1, params.Name + '_name', params.Name, 'dialogtable3');
                xlist2.connectXpop(Xpop2);
                break;
            case 'DOCS':
                var Xpop3 = new XTRpop('startXlist_' + params.Name, null, {
                    position: 'bottom',
                    delta_x: -203,
                    delta_y: 0,
                    donotdestroy: true
                });
                var xlist3 = this.group_dialog(Xpop3.tool_tip.id, 'load_xlist_docs', 1, params.Name + '_name', params.Name, 'dialogtable4');
                xlist3.connectXpop(Xpop3);
                break;
            case 'FAQ':
                var Xpop4 = new XTRpop('startXlist_' + params.Name, null, {
                    position: 'bottom',
                    delta_x: -203,
                    delta_y: 0,
                    donotdestroy: true
                });
                var xlist4 = this.group_dialog(Xpop4.tool_tip.id, 'load_xlist_faqs', 1, params.Name + '_name', params.Name, 'dialogtable5');
                xlist4.connectXpop(Xpop4);
                break;

            case 'DATE':
                new Control.DatePicker(params.Name, {
                    locale: 'ru',
                    use24hrs: true,
                    timePicker: true,
                    timePickerAdjacent: true
                });
                break;
            }
        }
    },

    load_properties: function (data, formname) {
        this.formcache = '';
        allcode = '';
        if (!Object.isUndefined(data)) {
            fdata = xoad.html.exportForm(formname);
            dataHash = $H(data);
            allcode += '<form id="' + formname + '">'
            dataHash.each(function (pair) {
                allcode += this.create_property_code(pair.value);

            }.bind(this));
            allcode += '</form>';
            allcode += this.formcache;
            this.source.innerHTML=allcode;
            if (fdata) xoad.html.importForm(formname, fdata);

            dataHash.each(function (pair) {
                this.create_property_binds(pair.value);
            }.bind(this));

        }

    },

    del: function (elt) {

        elt = Event.element(elt);
        if (elt.nodeName == 'SPAN') {
            elt = elt.up();
        }
        this.properties.get(elt._id)._source.remove();
        this.properties.unset(elt._id);


    },

    del_subgroup: function (elt) {
        elt = Event.element(elt);
        if (elt.nodeName == 'SPAN') {
            elt = elt.up();
        }
        this.subgroups.get(elt._id)._source.remove();
        this.subgroups.unset(elt._id);
    },


    edit_subgroup: function (evt) {
        elt = Event.element(evt);

        this.prop_editor_win = XTR_main.dhxWins.createWindow("prop_editor", 20, 10, 600, 600);
        this.prop_editor_win.centerOnScreen();
        this.prop_editor_win.setText(_lang_catalog['edit_property']);
        this.prop_editor_win.attachHTMLString(XTR_main.get_tpl('catalog', 'catalog_add_subprop', true));

        xoad.html.importForm('subgroup', this.subgroups.get(elt._id));
        this.current_sub = elt._id;

    },


    edit_property: function (evt) {
        elt = Event.element(evt);

        this.prop_editor_win = XTR_main.dhxWins.createWindow("prop_editor", 20, 10, 600, 600);
        this.prop_editor_win.centerOnScreen();
        this.prop_editor_win.setText(_lang_catalog['edit_property']);
        this.prop_editor_win.attachHTMLString(XTR_main.get_tpl('catalog', 'catalog_prop_editor', true));
        id = elt._id;
        xoad.html.importForm('one_property_edit', this.properties.get(id));
        $('property_type').appendChild(this.createTypeSelector(this.properties.get(id).Type))
        subgroups = new Hash();
        subgroups.set('_main_', _lang_catalog['main_group']);
        this.subgroups.each(function (pair) {
            subgroups.set(pair[0], pair[1].SubName)
        })

        this.current = id;
        this.attach_selector_options($('Prop_subgroup'), subgroups, this.properties.get(id)['Prop_subgroup']);
        switch (this.properties.get(id)['Type']) {
        case 'SELECTOR':
        case 'CURRENCY':

            this.create_SELECTOR(arr_convert_to_hash(this.properties.get(id)['catselector']));

            break;


        }

    },

    save_property: function () {

        _form = xoad.html.exportForm('one_property_edit');
        _form.catselector = (arr_convert_to_hash(_form.catselector)).toObject();
        exists = this.properties.find(function (s) {
            if (s[1].Name == _form.Name) {
                return true;
            }
        });
        if ((!Object.isUndefined(this.properties.get(this.current))) && (exists) && (this.properties.get(this.current)._id != this.current.id)) {
            alert(_lang_catalog['property_with_such_name_already_exists']);
            return void(0);

        }

        if (Object.isUndefined(this.properties.get(this.current))) {

            this.add_property_to_list(_form, true);
        } else {
            this.update_property_list(_form);
        }

        this.prop_editor_win.close();
    },

    add_property: function () {
        this.prop_editor_win = XTR_main.dhxWins.createWindow("prop_editor", 20, 10, 600, 600);
        this.prop_editor_win.centerOnScreen();
        this.prop_editor_win.setText(_lang_catalog['edit_property']);
        this.prop_editor_win.attachHTMLString(XTR_main.get_tpl('catalog', 'catalog_prop_editor', true));
        this.current = null;
        subgroups = new Hash();
        subgroups.set('_main_', _lang_catalog['main_group']);

        this.subgroups.each(function (pair) {
            subgroups.set(pair[0], pair[1].SubName);
        })

        this.attach_selector_options($('Prop_subgroup'), subgroups, null);
        $('property_type').appendChild(this.createTypeSelector());
    },




    update_property_list: function (item_data) {


        li = this.properties.get(this.current)._source;
        item_data._source = li;
        this.properties.set(this.current, item_data);
        li.down().update(item_data.Name);
        li.down().next(0).update(item_data.Alias);


    },

    create_sortable: function () {
        Sortable.create(this.source, {
            dropOnEmpty: true
        });
        Sortable.create(this.subgroups_source, {
            dropOnEmpty: true
        });
    },


    add_property_to_list: function (item_data, create_sortable) {

        li = new Element('li');


        li.update('<div >' + item_data.Name + '</div><div>' + item_data.Alias + '</div><div class="edit-prop"></div><div class="del-prop"></div>');

        id = getContinua();
        id = 'i_' + id;
        li.id = id;
        this.properties.set(id, item_data);

        this.source.appendChild(li);
        this.properties.get(id)._source = li;
        //  Sortable.destroy(this.source);
        if (create_sortable) {
            Sortable.create(this.source, {
                dropOnEmpty: true
            });
        }


        a = (new Element('a', {
            className: "button small",
            href: "javascript:void(0)"
        }).__extend({
            _id: id
        }).observe('click', this.edit_property.bind(this)));
        a.insert(_lang_common['edit']);

        a2 = (new Element('a', {
            className: "button small iclear",
            href: "javascript:void(0)"
        }).__extend({
            _id: id
        }).observe('click', this.del.bind(this)));
        a2.insert('<span></span>&nbsp;');
        li.down(1).next(0).appendChild(a);
        li.down(1).next(1).appendChild(a2);
    }

}


var XTRcatalog = Class.create();
var XTR_catalog;


XTRcatalog.prototype = Object.extend(new _modulePrototype(), {


    initialize: function () {
        this.destructable = true;
        this.current_node = null;
        this.module_name = 'catalog';
        this.template_changed = false;
        this.tree = null;
        this.sfields = new Array();
        this.cp_buffer = null;
        this.leaves_obj_type = new Array('_CATOBJ');
        this.init();
    },
    //m
    tree_object_clicked: function (itemid) {


        object_type = this.tree.getRowAttribute(itemid, "obj_type");

        switch (object_type) {
        case "_CATOBJ":
            this.show_edit_catobj(itemid);

            break;

        case "_CATGROUP":

            if (this.tabs.current == 't_manager') {
                this.manager_open(itemid);

            } else {
                this.show_edit_catgroup(itemid);
            }
            break;

        case "_ROOT":

            this.show_edit_root(itemid);

            break;

        default:
            return false;
        }
    },
    //m
    group_dialog: function () {
        columns = $H({
            image: ' ',
            name: _lang_common['name']
        });
        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H({
            image: 'IMAGE'
        });
        _images = $H({
            group: 'xres/ximg/tree/folderClosed.gif',
            page: 'xres/ximg/tree/page.gif'
        });

        if (!arguments[0]) {
            xlist_name = "xlist";
        } else {
            xlist_name = arguments[0];
        }
        if (!arguments[1]) {
            fcall = 'load_xlist_data';
        } else {
            fcall = arguments[1];
        }
        if (!arguments[2]) {
            startwith = 0
        } else {
            startwith = arguments[2];
        }
        if (!arguments[3]) {
            resultFunc = 0
        } else {
            resultFunc = arguments[3];
        }
        if (!arguments[4]) {
            resultSource = "showGroup";
        } else {
            resultSource = arguments[4];
        }

        return xlist = new XTRxlist(xlist_name, this.connector, {
            permanent: true,
            resultSource: resultSource,
            serverCallFunc: fcall,
            resultIDSource: resultSource + 'Id',
            columnsHeaders: columns,
            tableId: 'dialogtable',
            resultFunc: resultFunc,
            startWithAncestor: startwith,
            columnsHeadersWidth: _columnsHeadersWidth,
            columnsInterpretAs: _columnsInterpretAs,
            images: _images,
            className: 'dialog-table'
        });
    },




    show_edit_catgroup: function (itemId) {
        //debugger;
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_editgroup', true));
        $('addtunescontainer').update(XTR_main.get_tpl(this.module_name, 'add_tunes', true));

        this.connector.execute({
            load_catgroup_data: {
                group_id: itemId
            },
            change_property_set: true
        });
        result = this.connector.result;

        xoad.html.importForm('group_edit', result.catgroup_data);
        xoad.html.importForm('add_tunes', result.catgroup_data);

        this.current_node = itemId;

        this.change_property_set(result.catgroup_data.pset, 1);

        if (!Object.isUndefined(result.properties)) {
            if (!Object.isUndefined(result.properties.props)) {
                xoad.html.importForm('properties', result.properties.props);
            }
        }

        this.tabs.createTabNode({
            id: 'tedit_group',
            name: _lang_common['editing'],
            temporal: true
        }, 'top', true);




        this.objc = new XTRobjconnected('tobjc');

        if (!Object.isUndefined(result.connobjs)) {
            objc = $H(result.connobjs);
            objc.each(function (pair) {
                this.objc.add_objc(pair[1]);

            }.bind(this));

        }

        var Xpop = new XTRpop('startXlist', null, {
            position: 'bottom',
            delta_x: -350,
            delta_y: 0,
            donotdestroy: true
        });


        xlist = this.group_dialog(Xpop.tool_tip.id, 'load_xlist_data_all', 1, this.add_tobjc.bind(this));

        //Передача контекста Xpop
        xlist.connectXpop(Xpop);

        Validation.add('is-none-catobj', _lang_catalog['object_with_such_name_already_exists'], this.non_uniq_catobj.bind(this));


        this.validation_add = new Validation('add_tunes', {
            immediate: true,
            stopOnFirst: true
        });

        this.validation = new Validation('group_edit', {
            immediate: true
        });


    },


    show_edit_catobj: function (_id, context) {

        
        if (!context) {
            this.tabs.createTabNode({
                id: 'tedit_group',
                name: _lang_common['editing'],
                temporal: true
            }, 'top', true);
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_catobj_edit', true));

        } else {
            XTR_main.set_window_eform(XTR_main.get_tpl(this.module_name, 'catalog_catobj_edit', true));
        }

        $('addtunescontainer').update(XTR_main.get_tpl(this.module_name, 'add_tunes', true));

        this.current_node = _id;

        this.connector.execute({
            load_catobj_data: {
                id: _id
            },
            change_property_set: true
        });
        result = this.connector.result;


        xoad.html.importForm('add_tunes', result.catobj_data);
        xoad.html.importForm('catobj_edit', result.catobj_data);


        this.change_property_set(result.catobj_data.pset, 1);


        if (!Object.isUndefined(result.properties)) {
            if (!Object.isUndefined(result.properties.props)) {
                xoad.html.importForm('properties', result.properties.props);
            }

        }

        this.objc = new XTRobjconnected('tobjc');

        if (!Object.isUndefined(result.connobjs)) {
            objc = $H(result.connobjs);
            objc.each(function (pair) {
                this.objc.add_objc(pair[1]);

            }.bind(this));

        }


        var Xpop = new XTRpop('startXlist', null, {
            position: 'bottom',
            delta_x: -350,
            delta_y: 0,
            donotdestroy: true
        });

        xlist = this.group_dialog(Xpop.tool_tip.id, 'load_xlist_data_all', 1, this.add_tobjc.bind(this));


        //Передача контекста Xpop
        xlist.connectXpop(Xpop);


        Validation.add('is-none-catobj', _lang_catalog['object_with_such_name_already_exists'], this.non_uniq_catobj.bind(this));
        this.validation = new Validation('add_tunes', {
            immediate: true
        });

        this.validation2 = new Validation('catobj_edit', {
            immediate: true
        });



    },


    non_uniq_catobj: function (v) 
    {
        
        
        
        this.connector.execute({
            check_uniq: {
                basic: v,
                id:this.current_node
            }
        });

        if (!this.connector.result.uniq) {
            if (this.current_node != this.connector.result.id) {
                return false;
            }
        }

        return true;

    },



    show_catobj_list: function (o, name) {
        var Xpop = new XTRpop(o, null, {
            position: 'bottom',
            delta_x: -350,
            delta_y: 0,
            preventevents: true,
            donotdestroy: true
        });


        xlist = this.group_dialog(Xpop.tool_tip.id, 'load_xlist_data_all', 1);
        xlist.options.resultSource = name + 'Alias';
        xlist.options.resultIDSource = name;
        xlist.connectXpop(Xpop);
        Xpop.showTooltip();


    },

    add_tobjc: function (id, res) {
        params = {
            ConName: id,
            id: res
        }
        this.objc.add_objc(params);


    },

    show_new_catobj: function () {
        //debugger;
        this.tabs.makeActiveById('t_addcatobj');

        XTR_main.load_module_tpls(this.module_name, new Array('catalog_catobj_new'));


        _parentId = this.tree.getSelectedRowId();

        if (!_parentId) {
            _parentId = -1;
        } else {


            if (this.tree.getRowAttribute(_parentId, "obj_type") == '_CATOBJ') {
                _parentId = this.tree.getParentId(_parentId);

            }

        }


        this.connector.execute({
            show_new_catobj: {
                parent_id: _parentId
            }
        });

        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_catobj_new'));

        xoad.html.importForm('catobj_new', this.connector.result.catobj_data);

        if (this.connector.result.pset != null) {
            this.change_property_set(this.connector.result.pset);
        }

        var Xpop = new XTRpop('startXlist', null, {
            position: 'bottom',
            delta_x: -203,
            delta_y: 0,
            donotdestroy: true
        });

        xlist = this.group_dialog(Xpop.tool_tip.id, 'load_xlist_data_catobj', 1);




        xlist.connectXpop(Xpop);

        this.validation = new Validation('catobj_new', {
            immediate: true
        });
    },

    save_edited_catobj: function () 
    {

        
       if (!this.validation2.validate()) return void(0);
        if (!this.validation.validate()) return void(0);
        formdata = xoad.html.exportForm('catobj_edit');
        properties = xoad.html.exportForm('properties');
        objc = this.objc.get_objcs();
        add_tunes = xoad.html.exportForm('add_tunes');

        this.connector.execute({
            save_edited_catobj: {
                id: this.current_node,
                catobj_data: formdata,
                add_tunes: add_tunes,
                properties: properties,
                connobjs: objc
            }
        });


        this.tree.refreshItem(this.tree.getParentId(this.current_node));

        XTR_main.set_result(_lang_catalog['object_catalog_success_saved']);
    },


    save_new_catobj: function () {
        if (!this.validation.validate()) return void(0);

        formdata = xoad.html.exportForm('catobj_new');
        properties = xoad.html.exportForm('properties');
        this.connector.execute({
            save_new_catobj: {
                catobj_data: formdata,
                properties: properties
            }
        });
        this.tabs.createTabNode({
            id: 't_firstpage',
            name: _lang_common['info'],
            temporal: true
        }, 'top', true);

        this.tree.refreshItem(this.tree.getParentId(formdata.showGroupId));
        this.first_start();
        XTR_main.set_result(_lang_catalog['object_catalog_success_saved']);

    },


    save_new_catgroup: function () 
    {
        if (!this.validation.validate()) return void(0);
        formdata = xoad.html.exportForm('group_new');
        properties = xoad.html.exportForm('properties');
        this.connector.execute({
            save_new_catgroup: {
                data: formdata,
                properties: properties
            }
        });
        this.tabs.createTabNode({
            id: 't_firstpage',
            name: _lang_common['info'],
            temporal: true
        }, 'top', true);

        this.tree.refreshItem(this.tree.getParentId(formdata.showGroupId));
        this.first_start();
        XTR_main.set_result(_lang_common['group_success_saved']);
    },


    save_edited_catgroup: function () 
    {
        if (!this.validation.validate()) return void(0);
        if (!this.validation_add.validate()) return void(0);
        formdata = xoad.html.exportForm('group_edit');

        properties = xoad.html.exportForm('properties');
        objc = this.objc.get_objcs();
        add_tunes = xoad.html.exportForm('add_tunes');

        this.connector.execute({
            save_edited_catgroup: {
                id: this.current_node,
                data: formdata,
                properties: properties,
                tunes: add_tunes,
                connobjs: objc
            }
        });
        this.tabs.createTabNode({
            id: 't_firstpage',
            name: _lang_common['info'],
            temporal: true
        }, 'top', true);
        this.tree.refreshItem(this.tree.getParentId(this.current_node));
        XTR_main.set_result(_lang_common['group_success_saved']);

    },

    show_new_catgroup: function (_parentId) {
        //recieve tpl from server
        this.tabs.makeActiveById('t_addcatgroup');
        XTR_main.load_module_tpls(this.module_name, new Array('catalog_newgroup'));

        _parentId = this.tree.getSelectedRowId();

        if ((!_parentId) || (this.tree.getRowAttribute(_parentId, "obj_type") != '_CATGROUP')) {
            _parentId = 1;
        }

        this.connector.execute({
            show_new_catgroup: {
                parent_id: _parentId
            }
        });
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_newgroup'));
        xoad.html.importForm('group_new', this.connector.result.catgroup_data);
        var Xpop = new XTRpop('startXlist', null, {
            position: 'bottom',
            delta_x: -203,
            delta_y: 0,
            donotdestroy: true
        });

        xlist = this.group_dialog(Xpop.tool_tip.id);


        xlist.connectXpop(Xpop);

        this.validation = new Validation('group_new', {
            immediate: true
        });
    },


    change_property_set: function (_id) {
        if (!arguments[1]) this.connector.execute({
            change_property_set: {
                id: _id
            }
        });

        this.cat_editor = new XTRcateditor('properties_holder', true);
        if (this.connector.result != null) {
            this.cat_editor.load_properties(this.connector.result.prp, 'properties');
        }

    },


    del_propertyset: function (a, id) {
        id = this.property_gridlist.getSelectedRowId(1);
        if (id.length > 0) {
            if (!confirm(_lang_catalog['you_really_wish_to_remove_this_group_properties'])) {
                return false;
            }
            this.connector.execute({
                delete_property_set: {
                    id: id
                }
            });
            if (this.connector.result.deleted) {
                this.property_gridlist.deleteSelectedRows();
            }
        }
    },




    copy_propertyset: function (a, id) {

        if (!confirm(_lang_common['you_really_wish_to_copy_this_object'])) {
            return false;
        }
        this.connector.execute({
            copy_property_set: {
                id: id
            }
        });
        if (this.connector.result.isCopy) {
            this.property_sets();
        }
    },


    edit_propertyset: function (id) {

        XTR_main.load_module_tpls(this.module_name, new Array('catalog_editpropertyset'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_editpropertyset'));
        this.connector.execute({
            edit_property_set: {
                id: id
            }
        });
        this.current_property_set = id;

        xoad.html.importForm('property_set_new', this.connector.result.edit_form);
        if (!Object.isUndefined(this.connector.result.properties)) {
            this.cat_editor = new XTRcateditor('nproperties');

            if (!Object.isUndefined(this.connector.result.subgroups)) {
                $H(this.connector.result.subgroups).each(function (pair) {
                    pair[1].id = pair[0];
                    this.cat_editor.save_subgroup(pair[1], null, true);
                }.bind(this)

                )
            }

            $H(this.connector.result.properties).each(function (pair) {

                if (!Object.isUndefined(pair[1].params.Prop_subgroup)) {
                    pair[1].params.Prop_subgroup = 'i_' + pair[1].params.Prop_subgroup;
                }

                this.cat_editor.add_property_to_list(pair[1].params);

            }.bind(this)

            )
        }

        this.cat_editor.create_sortable();
    },


    add_propertyset: function () {


        XTR_main.load_module_tpls(this.module_name, new Array('catalog_addpropertyset'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_addpropertyset'));
        this.cat_editor = new XTRcateditor('nproperties');

        this.validation = new Validation('property_set_new', {
            immediate: true
        });


    },

    generate_link: function () {

        trans_rus_lat('Name', 'Basic', 250, true, true);
    },


    save_propertyset: function () {
        formdata = xoad.html.exportForm('property_set_new');
        pdata = this.cat_editor.get_properties_data();
        gdata = this.cat_editor.get_subgroups_data();
        this.connector.execute({
            save_propertyset: {
                id: this.current_property_set,
                propertySet: formdata,
                properties: pdata,
                subgroups: gdata
            }
        });
        XTR_main.set_result(_lang_catalog['group_properties_saved']);

    },
    save_new_propertyset: function () {
        if (!this.validation.validate()) return void(0);
        formdata = xoad.html.exportForm('property_set_new');
        pdata = this.cat_editor.get_properties_data();
        this.connector.execute({
            save_new_properyset: {
                propertySet: formdata,
                properties: pdata
            }
        });
        this.property_sets();

    },



/*    ps_gridview: function (id) {


    },
   */ 

    property_sets: function () {
        XTR_main.load_module_tpls(this.module_name, new Array('catalog_property_sets', 'catalog_prop_editor', 'catalog_add_subprop'));

        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_property_sets'), 'b');

        menu = new dhtmlXMenuObject();
        menu.renderAsContextMenu();
        menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false, '', '', this.del_propertyset.bind(this));
        menu.addNewChild(menu.topId, 0, "copy", _lang_common['copy'], false, '', '', this.copy_propertyset.bind(this));

        //    menu.attachEvent("onClick",this.gridclick.bind(this));
        this.property_gridlist = new dhtmlXGridObject('t-container');
        this.property_gridlist.selMultiRows = true;
        this.property_gridlist.setImagePath("xres/ximg/grid/imgs/");
        this.property_gridlist.setHeader('id,' + _lang_catalog['property_set']);
        this.property_gridlist.setInitWidths("100,*");
        this.property_gridlist.setColAlign("center,left");
        this.property_gridlist.attachHeader("#text_filter,#text_filter");
        this.property_gridlist.setColTypes("ed,ed");
        this.property_gridlist.setColSorting("int,str");
        this.property_gridlist.enableAutoWidth(true);
        this.property_gridlist.setMultiLine(false);

        this.property_gridlist.attachEvent("onRowDblClicked", this.edit_propertyset.bind(this));
        this.property_gridlist.enableContextMenu(menu);
        this.property_gridlist.init();
        this.property_gridlist.setSkin("modern");

        this.connector.execute({
            property_set_table: {
                id: id
            }
        });
        if (this.connector.result) {
            this.property_gridlist.parse(this.connector.result.data_set, "xjson")
        }
    },

    load_obj_properties: function (property_group) {
        this.connector.execute({
            load_: {
                parent_id: _parentId
            }
        });

    },


    first_start: function () {

        XTR_main.load_module_tpls(this.module_name, new Array('catalog_first'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_first'));

        this.tabs.makeActiveById('t_firstpage');
    },

    show_importXLS: function (id, zid) {
        this.currentId = zid;
        XTR_main.load_module_tpls(this.module_name, new Array('catalog_import_xls'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_import_xls'));
        this.connector.execute({
            show_importXLS: true
        });
        xoad.html.importForm('importXLS', this.connector.result.importXLS);

        var Xpop = new XTRpop('startXlist', null, {
            position: 'bottom',
            delta_x: -203,
            delta_y: 0,
            donotdestroy: true
        });

        xlist = this.group_dialog(Xpop.tool_tip.id, 'load_xlist_data_catobj', 1);
        xlist.connectXpop(Xpop);

        this.validation = new Validation('importXLS', {
            immediate: true
        });



    },

    column_builder: function (columns) {

        this.manager_columns = new Array;
        i = 2;
        for (column in columns) {

            i++;

            this.manager_columns[i] = columns[column].Name;

            if (columns[column].Alias) {

                switch (columns[column].Type) {
                case 'BOOL':
                    type = 'ch';
                    width = '70px'
                    break;
                    
                    
                case 'ICURRENCY':
                    type = 'ed';
                    width = '100px'
                    break;


                case 'IMAGE':
                    type = 'img';
                    width = '80px'
                    break;
                default:
                    type = 'ro';
                    width = '120px';
                }

                this.manager_gridlist.insertColumn(i, columns[column].Alias, type, width, 'str', 'center');
            }
        }



    },

    manager_open: function (id) {
        this.manager_gridlist.clearAll();
        c = this.manager_gridlist.getColumnsNum() - 1;

        while (c != 2) {
            this.manager_gridlist.deleteColumn(c);
            c = this.manager_gridlist.getColumnsNum() - 1;
        }
        //strange parameter
        this.manager_gridlist._cMod = null;

        this.connector.execute({
            manager: {
                ancestor: id
            }
        });
        this.column_builder(this.connector.result.columns);

        if (this.connector.result.data_set) {
            this.manager_gridlist.parse(this.connector.result.data_set, "xjson")
        }
        this.correctRheight();



    },

    delete_obj_grid_manager: function (kid, id) {

        this.delete_obj_grid(this.manager_gridlist);

    },

    manager_click: function (id) {
        cellObj = this.manager_gridlist.cellById(id, 1);



        switch (cellObj.getValue()) {
        case "_CATOBJ":

            this.prop_editor_win = XTR_main.dhxWins.createWindow("edit_object", 20, 10, 800, 730);
            this.prop_editor_win.centerOnScreen();
            this.prop_editor_win.setModal(false)
            this.prop_editor_win.setText(_lang_catalog['edit_property']);
            this.prop_editor_win.attachHTMLString('<div id="wineform"></div>');
            this.show_edit_catobj(id, true);
            break;

        case "_CATGROUP":
            this.manager_open(id);
            break;


        }
    },

    correctRheight: function () {
        this.manager_gridlist.forEachRow(function (id) {

            this.manager_gridlist._setRowHeight(id, 63);
        }.bind(this));

    },

    partial_save_checkbox: function (id, cid, state) {
        this.connector.execute({
            save_partial: {
                id: id,
                param: this.manager_columns[cid],
                value: state
            }
        });
    },

    show_manager: function () {


        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_manager', true), 'b');

        menu = new dhtmlXMenuObject();
        menu.renderAsContextMenu();
        menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false, '', '', this.delete_obj_grid_manager.bind(this));

        this.manager_gridlist = new dhtmlXGridObject('manager');
        this.manager_gridlist.selMultiRows = true;
        this.manager_gridlist.setImagePath("xres/ximg/grid/imgs/");
        this.manager_gridlist.setMultiLine(true);
        this.manager_gridlist.setHeader('id,obj_type,' + _lang_common['name']);
        this.manager_gridlist.setInitWidths("70,70,200");
        this.manager_gridlist.setColAlign("center,center,left");
        //this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter");
        this.manager_gridlist.setColTypes("ro,ro,ro");
        this.manager_gridlist.enableColumnMove(true);
        this.manager_gridlist.attachEvent("onCheckbox", this.partial_save_checkbox.bind(this));
        this.manager_gridlist.setColSorting("int,str,str");
        this.manager_gridlist.attachEvent("onRowDblClicked", this.manager_click.bind(this));
        this.manager_gridlist.enableContextMenu(menu);
        this.manager_gridlist.init();
        this.manager_gridlist.setSkin("modern");


        if (!(openid = this.tree.getSelectedRowId())) 
        {
            openid = 1;
        }
        this.connector.execute({
            manager: {
                ancestor: openid
            }
        });

        this.column_builder(this.connector.result.columns);

        if (this.connector.result.data_set) {
            this.manager_gridlist.parse(this.connector.result.data_set, "xjson")
        }
        this.manager_gridlist.enableAutoWidth(true, 737, 737);
        this.manager_gridlist.splitAt(3);
        this.manager_gridlist.setColumnHidden(1, true);
        this.manager_gridlist.enableHeaderMenu();
        this.correctRheight();


    },

    importXLS: function () {

        if (!this.validation.validate()) return void(0);
        formdata = xoad.html.exportForm('importXLS');
        this.connector.execute({
            importXLS: {
                id: this.currentId,
                data: formdata,
                step: 'parse',
                step_l: formdata.step_l
            }
        });
        if (this.connector.result.parseComplete) {
            this.connector.result.dataWriteEnd = 0;
            while (this.connector.result.dataWriteEnd != 1) {
                i++;
                if (i > 1000) break;
                $('loading_progress').update(i * parseInt(formdata.step_l));
                this.connector.execute({
                    importXLS: {
                        id: this.currentId,
                        data: formdata,
                        step: 'datawrite'
                    }
                });

            }
            $('loading_progress').update(_lang_catalog['loading_is_finished']);
            this.tree.refreshItem(this.currentId);
        }


    },
    show_exportjson: function (id) {
        this.currentId = id;
        XTR_main.load_module_tpls(this.module_name, new Array('catalog_export_json'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_export_json'));
        xoad.html.importForm('exportJSON', {
            filename: translit(this.tree.getItemText(id))
        });
        this.validation = new Validation('exportJSON', {
            immediate: true
        });



    },

    start_export: function () {
        if (!this.validation.validate()) return void(0);
        formdata = xoad.html.exportForm('catalogExport');
        formdata['id'] = this.currentId;
        this.connector.execute({
            catalogExport: formdata
        });
        if (!this.connector.result.ERROR) {
            $('downloadfile').href = this.connector.result.uploadfile;
            $('downloadfile').update(_lang_common['download_file'] + ' ' + this.connector.result.uploadfile);

        } else {
            switch (this.connector.result.ERROR) {
            case 1:
                er = _lang_catalog['export_data_not_found'];
                break;
            case 2:
                er = _lang_catalog['export_filewrite_fail'];
                break;
            }
            growlerr.error(er, {
                sticky: true
            });
            $('downloadfile').update();


        }

    },
    show_export: function (kid, id) {
        this.currentId = id;
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_export', true));
        this.connector.execute({
            showExport: true
        });
        if (this.connector.result.catalogExport) {
            if (!this.connector.result.catalogExport['exportWritable']) {
                growlerr.warn(_lang_catalog['warning_export_not_writable'], {
                    life: 5
                });
            }
            this.connector.result.catalogExport['filename'] = translit(this.tree.getItemText(id));
            xoad.html.importForm('catalogExport', this.connector.result.catalogExport);
        }

        this.validation = new Validation('catalogExport', {
            immediate: true
        });

    },
    exportjson: function () {
        if (!this.validation.validate()) return void(0);
        formdata = xoad.html.exportForm('exportJSON');
        this.connector.execute({
            exportJSON: {
                id: this.currentId,
                filename: formdata.filename
            }
        });
        if (this.connector.result.ERROR == null) {
            $('downloadfile').href = this.connector.result.filepath;
            $('downloadfile').update(_lang_common['download_file'] + ' ' + this.connector.result.filepath);
        }

    },

    dynXLS: function (id) {

        this.connector.execute({
            catalog_table: {
                id: id
            }
        });
        if (this.connector.result) {
            this.tree.refreshFilters();
            this.tree.json_dataset = this.connector.result.data_set;
        }
        return true;
    },


    switch_catalog_obj: function (id, cid, state) {

        this.connector.execute({
            switch_catalog_obj: {
                id: id,
                state: state
            }
        });

    },

    set_permissions:function(kid, id)
    {
        XTR_main.load_module('users','silent');
        XTR_users.show_permissions(id,'catalog');
    },
    

    build_interface: function () {

        toggle_main_menu(true);

        if (!this.tree) {


            tree_node = new Element('div', {
                id: this.module_name + "_treebox",
                className: 'gridbox'
            });
            $('tp-tree-window').appendChild(tree_node);
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false, '', '', function (id, zid) {
                this.tree.refreshItem(zid);
            }.bind(this));
            menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false, '', '', this.delete_obj_treegrid.bind(this));
            menu.addNewChild(menu.topId, 0, "copyn", _lang_common['copy'], false, '', '', this.copy_obj_treegrid.bind(this));
            menu.addNewChild(menu.topId, 0, "pasten", _lang_common['paste'], false, '', '', this.paste_obj_treegrid.bind(this));
            menu.addNewChild(menu.topId, 0, "add_object", _lang_catalog['add_object'], false, '', '', this.show_new_catobj.bind(this));
            menu.addNewChild(menu.topId, 0, "add_group", _lang_common['add_category'], false, '', '', this.show_new_catgroup.bind(this));
            menu.addNewChild(menu.topId, 0, "show_export", _lang_common['export'], false, '', '', this.show_export.bind(this));

            menu.addNewChild(menu.topId, 0, "show_importEXCEL", _lang_catalog['import_from_excel'], false, '', '', this.show_importXLS.bind(this));
            menu.addNewChild(menu.topId, 0, "show_permissions", _lang_common['set_permissions'], false, '', '', this.set_permissions.bind(this));



            this.tree = new dhtmlXGridObject(this.module_name + "_treebox");
            this.tree.selMultiRows = true;
            this.tree.imgURL = "/xres/ximg/green/";



            this.tree.setHeader(_lang_catalog['catalog_objects'] + ',' + _lang_catalog['propert_set'] + ',id,' + _lang_pages['link'] + ',&nbsp');
            this.tree.setInitWidths("280,*,60,50,30");
            this.tree.setColAlign("left,left,left,left,center");
            this.tree.setColTypes("tree,ed,ro,ro,ch");
            this.tree.enableDragAndDrop(true);
            this.tree.enableEditEvents(false, false, true);
            this.tree.setSelectFilterLabel(3, _select_filter_yes_no);
            this.tree.attachEvent("onDrag", this.on_treegrid_drag.bind(this));
            this.tree.setDragBehavior('complex');
            this.tree.enableMultiselect(true);
            this.tree.enableContextMenu(menu);
            this.tree.attachHeader("#text_search,#text_search,#text_filter,#text_filter,#select_filter");


            this.tree.init();
            this.tree.kidsXmlFile = 1;
            this.tree.attachEvent("onCheckbox", this.switch_catalog_obj.bind(this));
            this.tree.attachEvent("onDynXLS", this.dynXLS.bind(this));
            this.tree.setSkin("dhx_skyblue");
            this.tree.attachEvent("onRowDblClicked", this.tree_object_clicked.bind(this));
            this.connector.execute({
                catalog_table: {
                    id: 0
                }
            });
            if (this.connector.result) {

                this.tree.parse(this.connector.result.data_set, "xjson")
            }
            this.tree.openItem(1);



        } else {
            $(this.module_name + "_treebox").show();
        }


        //закладки
        var oTabs = [{
            id: 't_firstpage',
            name: _lang_common['info'],
            temporal: true
        }, {
            id: 't_addcatobj',
            name: _lang_catalog['add_object'],
            callback: this.show_new_catobj.bind(this)
        }, {
            id: 't_addcatgroup',
            name: _lang_common['add_category'],
            callback: this.show_new_catgroup.bind(this)
        }, {
            id: 't_addpropgroup',
            name: _lang_catalog['groups_properties'],
            callback: this.property_sets.bind(this)
        },

        {
            id: 't_search_forms',
            name: _lang_catalog['search_forms'],
            callback: this.search_forms.bind(this)
        }, {
            id: 't_manager',
            name: _lang_common['manager'],
            callback: this.show_manager.bind(this)
        }


        /*,{id:'t_tunes',name: _lang_common['options'], callback: this.show_tunes.bind(this)}*/

        ]

        this.tabs = new XTRFabtabs('bookmarks', oTabs);



        //cтартуем начальную страницу модуля
        this.tree.refreshItem(1);
        this.first_start();

        toggle_main_menu(true);
    },

    add_sform: function () {
        this.currentId = null;
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_sforms_add', true));
        this.validation = new Validation('add_sform', {
            immediate: true
        });
    },

    show_sfield_editor: function (id) {

        mw = XTR_main.dhxWins.createWindow("sfield_editor", 20, 10, 438, 500);
        mw.centerOnScreen();
        mw.setText(_lang_catalog['edit_sfrom']);
        mw.setModal(true);
        mw.attachHTMLString(XTR_main.get_tpl('catalog', 'catalog_sforms_editor', true));


        but = $$('#add_sfield a.button')[0];
        this.connector.execute({
            get_property_set_sfields: true
        });
        xoad.html.importForm('add_sfield', this.connector.result['add_sfield']);
        if (id) {
            but.writeAttribute('rel', id)
            but.update(_lang_common['edit_field']);
            this.change_sform_property_set(this.sfields[id].property_set);
            xoad.html.importForm('add_sfield', this.sfields[id]);
        } else {

            but.writeAttribute('rel', '')

        }


        this.sfield_validation = new Validation('add_sfield', {
            immediate: true
        });

    },

    add_sfield_handler: function (a) {
        sform = xoad.html.exportForm('add_sfield');

        if ($('add_sfield').property.selectedIndex != -1) {
            property_text = $('add_sfield').property.options[$('add_sfield').property.selectedIndex].text
        }

        if ($('add_sfield').property_set.selectedIndex != -1) {
            property_set_text = $('add_sfield').property_set.options[$('add_sfield').property_set.selectedIndex].text

        }

        if ($('add_sfield').criteria.selectedIndex != -1) {
            criteria_text = $('add_sfield').criteria.options[$('add_sfield').criteria.selectedIndex].text

        }
        sform._fields = {
            property: property_text,
            property_set: property_set_text,
            criteria: criteria_text
        }

        id = a.readAttribute('rel')

        if (this.sfield_validation.validate()) {
            if (!id) {
                this.add_sfield(sform);
                Sortable.create('sfields', {
                    tag: 'div',
                    constraint: false
                });
            } else {
                this.sfields[id] = sform;
                this.fresh_sfield($(id), sform);
            }
            XTR_main.dhxWins.window('sfield_editor').close();
        }

    },

    save_sform: function () {
        if (!this.validation.validate()) return void(0);

        slo = Sortable.sequence('sfields');
        sfields = new Array();

        for (i = 0; i < slo.size(); i++) {
            sfields[slo[i]] = this.sfields['s_' + slo[i]];
        }

        this.connector.execute({
            save_sform: {
                sform: xoad.html.exportForm('add_sform'),
                sfields: sfields,
                id: this.currentId
            }
        });

        this.search_forms();
    },


    del_sform: function (a, id) {
        if (!confirm(_lang_catalog['you_really_wish_to_remove_this_search_form'])) {
            return false;
        }
        this.connector.execute({
            del_sform: {
                id: id
            }
        });
        if (this.connector.result.del_sform) {
            this.search_forms();
        }

    },

    edit_sform: function (id) {
        this.connector.execute({
            edit_sform: {
                id: id
            }
        });
        this.currentId = id;
        this.sfields = new Array();
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_sforms_edit', true));
        xoad.html.importForm('add_sform', this.connector.result['sform']);

        if (sfields = $H(this.connector.result.sfields)) {
            sfields.each(function (v) {
                this.add_sfield(v.value);
            }.bind(this))

            Sortable.create('sfields', {
                tag: 'div',
                constraint: false
            });

        }

        this.validation = new Validation('add_sform', {
            immediate: true
        });



    },
    copy_sform: function (a, id) {

        if (!confirm(_lang_common['you_really_wish_to_copy_this_object'])) {
            return false;
        }

        this.connector.execute({
            copy_sform: {
                id: id
            }
        });
        if (this.connector.result.isCopy) {
            this.search_forms();
        }
    },



    edit_sfield: function (elt) {

        elt = Event.element(elt)
        id = elt.up().id;
        this.show_sfield_editor(id);
    },

    delete_sfield: function (elt)

    {
        elt = Event.element(elt)
        delete this.sfields[elt.up().id];
        elt.up().remove();

    },

    fresh_sfield: function (sf, sfield) {
        sf.update();
        sf.insert('<div class="i5"><p><strong>' + sfield.sname + '</strong></p><p>' + sfield._fields.property_set + ' | ' + sfield._fields.property + ' | ' + sfield._fields.criteria + ' </p></div>');
        a_del = new Element('a', {
            href: 'javascript:void(0)'
        }).update(_lang_common['delete']);
        a_del.style.paddingRight = '5px';
        a_edit = new Element('a', {
            href: 'javascript:void(0)'
        }).update(_lang_common['edit']);
        sf.insert(a_del);
        sf.insert(a_edit);
        a_del.observe('click', this.delete_sfield.bind(this));
        a_edit.observe('click', this.edit_sfield.bind(this));
    },

    add_sfield: function (sfield) {
        sfield.id = 's_' + getContinua();
        this.sfields[sfield.id] = (sfield);
        sf = new Element('div', {
            id: sfield.id,
            className: 'field-form'
        });
        sf.style.width = '280px';
        this.fresh_sfield(sf, sfield);
        $('sfields').appendChild(sf);

    },

    change_sform_property_set: function (val) {

        this.connector.execute({
            get_properties_sfields: {
                id: val
            }
        });
        $A($('add_sfield').property.options).invoke('remove');
        xoad.html.importForm('add_sfield', this.connector.result['add_sfield']);

    },

    search_forms: function () {
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'catalog_sforms', true), 'b');

        menu = new dhtmlXMenuObject();
        menu.renderAsContextMenu();
        menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false, '', '', this.del_sform.bind(this));
        menu.addNewChild(menu.topId, 0, "copy", _lang_common['copy'], false, '', '', this.copy_sform.bind(this));

        gridlist = new dhtmlXGridObject('t-container');
        gridlist.selMultiRows = true;
        gridlist.setImagePath("xres/ximg/grid/imgs/");
        gridlist.setHeader('id,' + _lang_catalog['search_form']);
        gridlist.setInitWidths("100,*");
        gridlist.setColAlign("center,left");
        gridlist.attachHeader("#text_filter,#text_filter");
        gridlist.setColTypes("ed,ed");
        gridlist.setColSorting("int,str");
        gridlist.enableAutoWidth(true);
        gridlist.setMultiLine(false);
        gridlist.attachEvent("onRowDblClicked", this.edit_sform.bind(this));
        gridlist.enableContextMenu(menu);
        gridlist.init();
        gridlist.setSkin("modern");

        this.connector.execute({
            sforms_table: {
                id: id
            }
        });
        if (this.connector.result) {
            gridlist.parse(this.connector.result.data_set, "xjson")
        }

    },



    /*--a_interface-*/
    get_action_properties: function (_action, prefix) {
        if (prefix != 'action' && prefix != 'secondary') {
            destination_prefix = 'action';
            defaultAction = prefix;
        } else if (prefix == 'secondary') {
            destination_prefix = prefix;
            defaultAction = null;
        } else {


            destination_prefix = 'action';
            defaultAction = null;
        }
        this.connector.execute({
            get_action_properties: {
                Action: _action
            }
        });

        if (this.connector.result.action_properties) {
            $(destination_prefix + '_properties').update(this.connector.lct.action_properties);
        } else {
            $(destination_prefix + '_properties').update('Свойства отсутствуют');
        }

        xoad.html.importForm('tune_actions', this.connector.result.action_properties_form);
        xoad.html.importForm('tune_actions', this.connector.result.catalog_data);



        if (this.connector.result.xlist) {
            Xpop2 = new XTRpop('startXXlist', null, {
                position: 'bottom',
                delta_x: -272,
                delta_y: -120,
                donotdestroy: true
            });
            gd = this.group_dialog(Xpop2.tool_tip.id);
            gd.connectXpop(Xpop2);

            this.validation = new Validation('tune_actions', {
                immediate: true
            });
        }

        if (this.connector.result.url_point_xlist) {

            Xpop = new XTRpop('startXXXlist', null, {
                position: 'bottom',
                delta_x: -272,
                delta_y: -120,
                donotdestroy: true
            });
            hd = this.group_dialog(Xpop.tool_tip.id, null, null, null, 'showBasicPoint');
            hd.connectXpop(Xpop);
        }

        if (defaultAction) {
            this.get_action_properties(defaultAction, 'secondary');
        }
        this.validation = new Validation('tune_actions', {
            immediate: true
        });

    },

/*
           
            get_ainterface:function(alias)
            {
               XTR_main.load_module_tpls('admin', new Array('ainterface'));             
               this.connector.execute({load_actions:true});                                                                                     
               $('ainterface').update(XTR_main.get_tpl('admin', 'ainterface'));                               
               this.connector.result.tune_actions.module_alias=alias;
               xoad.html.importForm('tune_actions',this.connector.result.tune_actions);   
               this.validation=new Validation('tune_actions', {immediate : true}); 
            
            },
              */

    /*--/a_interface-*/

    destructor: function () {

        //очистка за slotz
        this.tabs.destructor();
        $(this.module_name + "_treebox").hide();
        XTR_main.set_rightside_eform('');

    }
});

/*-------------------------------cateditor-----------------------------------------------------------*/