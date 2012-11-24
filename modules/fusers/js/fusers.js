var XTRfusers = Class.create();
var XTR_fusers;

XTRfusers.prototype = Object.extend(new _modulePrototype(), {
    initialize: function () {
        this.destructable = true;
        this.current_node = null;
        this.module_name = 'fusers';
        this.tree = null;
        this.leaves_obj_type = new Array('_FUSERS');
        this.init();
        this.gridlist = null;
    },


    tree_object_clicked: function (itemid) {
        object_type = this.tree.getNodeParam(itemid, 'obj_type');

        switch (object_type) {
        case "_FUSER":
            this.show_edit_fuser(itemid);

            break;

        case "_FUSERSGROUP":
            this.gridview(itemid);

            break;


        default:
            return false;
        }
    },
    //m
    show_tunes: function () {



        this.connector.execute({
            edit_tunes: true
        });
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'tunes', true));
        xoad.html.importForm('tunes', this.connector.result.tunes);

        this.validation = new Validation('tunes', {
            immediate: true
        });

    },

    save_tunes: function (e) {
        this.connector.execute({
            save_tunes: xoad.html.exportForm('tunes')
        });
        XTR_main.set_result(_lang_fusers['options_saved'], e);

    },

    show_new_fuser: function () {


        this.connector.execute({
            show_new_fuser: true
        });
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_fuser', true));
        xoad.html.importForm('fuser_new', this.connector.result.fuser_data);
        Validation.add('is-none-user-uniq', _lang_fusers['user_with_such_name_already_exists'], this.non_uniq_user.bind(this));

        this.validation = new Validation('fuser_new', {
            immediate: true
        });

    },

    non_uniq_user: function () {
        this.connector.execute({
            check_uniq: {
                username: v
            }
        });
        return this.connector.result.uniq;
    },


    save_new_fuser: function () {

        if (!this.validation.validate()) return void(0);
        formdata = xoad.html.exportForm('fuser_new');
        this.connector.execute({
            save_new_fuser: {
                data: formdata
            }
        });
        this.tabs.createTabNode({
            id: 't_firstpage',
            name: _lang_common['info'],
            temporal: true
        }, 'top', true);
        this.tree.refreshItem(formdata['FUserGroup']);
        this.first_start();
        XTR_main.set_result(_lang_fusers['user_success_saved']);

    },


    save_fuser: function (e) {


        if (!this.validation.validate()) return void(0);

        formdata = xoad.html.exportForm('fuser_edit');
        this.connector.execute({
            save_fuser: {
                id: this.current_node,
                data: formdata
            }
        });
        this.tree.refreshItem(this.tree.getParentId(this.current_node));
        XTR_main.set_result(_lang_fusers['user_success_saved'], e);

    },

    show_fusergroup_new: function () {
        this.show_fusergroup();
    },

    show_fusergroup: function (b, id) {
        XTR_main.load_module_tpls(this.module_name, new Array('add_fuser_group'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_fuser_group', true));
        if (!Object.isUndefined(id)) {
            this.connector.execute({
                load_fuser_group: {
                    id: id
                }
            });
            this.current_node = id;
            
            
            xoad.html.importForm('fgroup_new', this.connector.result.fusergroup);
        } else {
            this.current_node = null;
        }

        this.validation = new Validation('fgroup_new', {
            immediate: true
        });
    },



    save_fusergroup: function (id) {


        if (!this.validation.validate()) return void(0);
        if (this.current_node != null) {
            id = this.current_node;
            this.current_node = null;
        } else {
            id = false;
        }
        formdata = xoad.html.exportForm('fgroup_new');
        this.connector.execute({
            save_fusergroup: {
                id: id,
                data: formdata
            }
        });

        if (this.current_node == null) {
            this.tabs.createTabNode({
                id: 't_firstpage',
                name: _lang_common['info'],
                temporal: true
            }, 'top', true);
        }

        this.tree.refreshItem(this.tree.getParentId(this.current_node));
        this.first_start();
        XTR_main.set_result(_lang_fusers['group_users_success_saved']);



    },

    _delete_obj_grid:function()
    {
             this.delete_obj_grid(this.gridlist);
    },
    

    gridview: function (id) {
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'fusers_first', true), 'b');
        $('pw-edit').insert('<div id="t-container"></div>');
        menu = new dhtmlXMenuObject();
        menu.renderAsContextMenu();
        menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false,'','',this._delete_obj_grid.bind(this));
        //                    menu.attachEvent("onClick",this.gridclick.bind(this));
        this.gridlist = new dhtmlXGridObject('t-container');
        this.gridlist.selMultiRows = true;
        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
        this.gridlist.setHeader('id,' + _lang_fusers['login'] + ',' + _lang_fusers['Name'] + ',Email,' + _lang_common['active']);
        this.gridlist.setInitWidths("50,100,250,*");
        this.gridlist.setColAlign("right,left,left,left");
        this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#select_filter");
        this.gridlist.setColTypes("ed,ed,ed,ed,ch");
        this.gridlist.setColSorting("int,str,str,str,ch");
        this.gridlist.enableAutoWidth(true);        
        this.gridlist.setSelectFilterLabel(4,_select_filter_yes_no);
        this.gridlist.enableDragAndDrop(true);

/*     this.gridlist.gridToTreeElement = function(treeObj, treeNodeId, gridRowId) 
                    {
                        debugger;
                        return this.cells(gridRowId, 1).getValue() + "/" + this.cells(gridRowId, 2).getValue();
                    }
                   */


        this.gridlist.attachEvent("onCheckbox", this.user_active.bind(this));
        this.gridlist.attachEvent("onRowDblClicked", this.show_edit_fuser.bind(this));
        this.gridlist.enableContextMenu(menu);
        this.gridlist.init();
        this.gridlist.setSkin("modern");
        this.gridlist.rowToDragElement = function(id) 
                        {
                                if (this.cells(id, 2).getValue() != ""){return this.cells(id, 2).getValue() + "/" + this.cells(id, 1).getValue();}else
                                {
                                    return this.cells(id, 1).getValue();
                                }
                        }

    
    this.gridlist.gridToTreeElement = function(tree,fakeID,gridID,treeID)
                        {
                            
                                this.connector.execute({changeAncestor:{ancestor:treeID,id:gridID}});            
                                if(this.connector.result.dragOK)
                                {
                                    XTR_main.set_result(_lang_fusers['user_moved']);
                                    return true;
                                }else{
                                    return -1;
                                }
                            
                        }.bind(this);
        
        this.connector.execute({
            fusers_table: {
                id: id
            }
        });
        if (this.connector.result) {
            this.gridlist.parse(this.connector.result.data_set, "xjson")
        }
    },


    user_active: function (rId, cInd, state) {
        this.connector.execute({
            user_active: {
                id: rId,
                state: state
            }
        });
    },

    first_start: function () {
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'fusers_first', true));
    },

    show_edit_fuser: function (userId) {


        this.connector.execute({
            load_fuser_data: {
                id: userId
            }
        });
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_fuser', true));
        xoad.html.importForm('fuser_edit', this.connector.result.fuser);

        this.current_node = userId;
        this.tabs.createTabNode({
            id: 'tedit_user',
            name: _lang_common['editing'],
            temporal: true
        }, 'top', true);

        Validation.add('is-none-user-uniq', _lang_fusers['user_with_such_name_already_exists'], this.non_uniq_user.bind(this));

        if (!Object.isUndefined(ex = $H(this.connector.result.fuser.extdata))) {
            t = '<ul>';
            ex.each(function (pair) {
                t += '<li><p><strong>' + pair[0] + '</strong>: ' + pair[1] + '</p><li>';
            });

            t += '</ul>';
            $('extdata').update(t);
        }

        this.validation = new Validation('fuser_edit', {
            immediate: true
        });

    },






    build_interface: function () {
toggle_main_menu(true);
        //дерево        
        if (!this.tree) {

            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "add_user", _lang_fusers['new_user'], false, '', '', this.show_new_fuser.bind(this));
            menu.addNewChild(menu.topId, 0, "add_category", _lang_common['add_category'], false, '', '', this.show_fusergroup_new.bind(this));
            menu.addNewChild(menu.topId, 0, "edit_category", _lang_news['edit_category'], false, '', '', this.show_fusergroup.bind(this));
            menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false, '', '', this.delete_obj.bind(this));

            $('tp-tree-window').appendChild(new Element('div', {
                id: this.module_name + "_treebox",
                className: 'treebox'
            }));
            this.tree = new dhtmlXTreeObject(this.module_name + "_treebox", 'auto', 'auto', "0", "xres/ximg/tree/");
            this.tree.setImagePath("/xres/ximg/green/");
            this.tree.setDataMode("json");
            this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this));
            this.tree.enableMultiselection(1);
            this.tree.enableContextMenu(menu);
            this.tree.enableDragAndDrop(true);  
            this.tree.setXMLAutoLoading("tree_xml_server.php?tree=fusers_container");
            this.tree.loadJSON("tree_xml_server.php?tree=fusers_container&id=0", function () {
                this.tree.refreshItem(1);
            }.bind(this));

        } else {

            $(this.module_name + "_treebox").show();
        }



        //закладки
        var oTabs = [{
            id: 't_addfuser',
            name: _lang_fusers['new_user'],
            callback: this.show_new_fuser.bind(this)
        }, {
            id: 't_addfugroup',
            name: _lang_common['add_group'],
            callback: this.show_fusergroup.bind(this)
        }, {
            id: 't_tunes',
            name: _lang_common['options'],
            callback: this.show_tunes.bind(this)
        }]


        //    this.show_new_fuser();
        this.first_start();
        this.tabs = new XTRFabtabs('bookmarks', oTabs);
        //       this.tabs.makeActiveById('t_addfuser');
        //    this.connector.execute({get_tree_inheritance:true});        
        //    this.tree.setInheritanceArr(this.connector.result.tree_inheritance);

        //cтартуем начальную страницу модуля
        this.connector.onexecuteError = function (error) {
            alert(_lang_common['error_on_server'] + '\n\n' + error.message);
            return true;
        };


    },

    checksub: function (inp) {
        ul = inp.next();
        chxs = ul.getElementsBySelector('input');
        chxs.each(function (ch) {

            ch.checked = inp.checked;
        });
    },

    save_tunes: function () {
        XTR_main.show_loading();
        formdata = xoad.html.exportForm('tunes');
        this.connector.execute({
            save_tunes: {
                tunes: formdata
            }
        });
        XTR_main.hide_loading();
        XTR_main.set_result(_lang_fusers['options_saved']);
    },


    get_action_properties: function (_action, prefix) {
        if (prefix != 'action' && prefix != 'secondary') {
            destination_prefix = 'action';
            defaultAction = prefix;
        }
        else if (prefix == 'secondary') {
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

        if (this.connector.result.xlist) {
            var Xpop = new XTRpop('startXXlist', null, {
                position: 'bottom',
                delta_x: -272,
                delta_y: -60
            });
            gd = XTR_pages.page_dialog(Xpop.tool_tip.id);
            gd.connectXpop(Xpop);
        }


        if (defaultAction) {
            this.get_action_properties(defaultAction, 'secondary');
        }
    },


    destructor: function () {
        // tree.destructor();
        //очистка за slotz
        $(this.module_name + "_treebox").hide();

        this.tabs.destructor();


    }
});