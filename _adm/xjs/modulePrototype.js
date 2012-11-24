var Connector = Class.create();

xConnector.clear_module_vars = function ()
{
    this.lct = null;
    this.result = null;
}

xConnector.onexecuteError = function (error)
{
    alert(_lang_common['error_on_server'] + '\n\n' + error.message);
    return true;
}

Connector.prototype = {
    module: null,
    result: null,
    routetype: null,
    lct: null,
    executionTable: new Array(),

    initialize: function (module)
    {
        this.module = module;
        this.routetype = arguments[1];
    },
    //function(data,(arg)) 
    //если  arg - то выполнение откладывается до первого вызова
    //arg (2) - модель роутинга
    onerror: function (error)
    {;
        for (i = 0; i < error.length; i++)

        growlerr.error(error[i].message, {
            sticky: true
        });
    },

    onmessage: function (message)
    {

        for (i = 0; i < message.length; i++)

        growlerr.info(message[i].message, {
            life: 2
        });

    },

    execute: function (data)
    {
        d = new Array();
        d[this.module] = data;
        this.result = null;

        if (arguments[1])
        {

            if (this.executionTable[this.module])
            {
                this.executionTable[this.module] = array_merge(d, this.executionTable[this.module]);

            }
            else
            {
                this.executionTable[this.module] = data;
            }

        }
        else
        {

            if (this.executionTable.length > 0)
            {
                d = array_merge(d, this.executionTable);
            }
            if (this.routetype)
            {
                routetype = this.routetype;
            }
            else
            {
                routetype = arguments[2];
            }
            switch (routetype)
            {
            case 'route':
                xConnector.route(d);
                break;

            case 'incroute':
                xConnector.incroute(d);
                break;

            default:
                xConnector.xroute(d);
                break;
            }

            if (xConnector.error != null)
            {
                this.onerror(xConnector.error);
                this.error = true;

            }

            if (xConnector.message != null)
            {
                this.onmessage(xConnector.message);
                this.message = true;

            }

            this.result = xConnector.result;
            this.lct = xConnector.lct;


            this.executionTable = new Array();
        }
    }
}

var _modulePrototype = Class.create();

_modulePrototype.prototype = {
    initialize: function ()
    {},

    get_action_properties: function (_action, prefix)
    {
        if (prefix != 'action' && prefix != 'secondary')
        {
            destination_prefix = 'action';
            defaultAction = prefix;
        }
        else if (prefix == 'secondary')
        {
            destination_prefix = prefix;
            defaultAction = null;
        }
        else
        {
            destination_prefix = 'action';
            defaultAction = null;
        }

        this.connector.execute(
        {
            get_action_properties: {
                Action: _action
            }
        });

        if (this.connector.result.action_properties)
        {
            $(destination_prefix + '_properties').update(this.connector.lct.action_properties);
        }
        else
        {
            $(destination_prefix + '_properties').update(_lang_common['properties_are_absent']);
        }

        xoad.html.importForm('tune_actions', this.connector.result.action_properties_form);

        if (this.connector.result.xlist)
        {
            var Xpop = new XTRpop('startXXlist', null, {
                position: 'bottom',
                delta_x: -272,
                delta_y: -60
            });
            gd = this.group_dialog(Xpop.tool_tip.id);
            gd.connectXpop(Xpop);
        }

        if (defaultAction)
        {
            this.get_action_properties(defaultAction, 'secondary');
        }

        this.validation = new Validation('tune_actions', {
            immediate: true
        });


    },

    get_ainterface: function ()
    {
        XTR_main.load_module_tpls('admin', new Array('ainterface'));
        this.connector.execute(
        {
            load_actions: true
        });
        $('ainterface').update(XTR_main.get_tpl('admin', 'ainterface'));
        xoad.html.importForm('tune_actions', this.connector.result.tune_actions);
        this.avalidation = new Validation('tune_actions', {
            immediate: true
        });
    },

    copy_obj_treegrid: function ()
    {
        this.cp_buffer = this.tree.getSelectedRowId(true);

    },


    paste_obj_treegrid: function (zid, anc)
    {
        if (this.cp_buffer == null)
        {
            return false;
        }

        obj_type = this.tree.getRowAttribute(anc, "obj_type");

        if (this.leaves_obj_type.indexOf(obj_type) == false)
        {
            anc = this.tree.getParentId(itemId);
        }



        this.connector.execute(
        {
            _copy: {
                anc: anc,
                node: this.cp_buffer
            }
        });

        if (this.connector.result.nodecopy)
        {
            this.tree.refreshItem(anc);
        }
    },


    copy_obj: function ()
    {

        selected = this.tree.getSelectedItemId(true);
        this.cp_buffer = selected;
    },

    paste_obj: function (b, anc)
    {

        if (this.cp_buffer == null)
        {
            return false;
        }

        obj_type = this.tree.get_objtype(anc);

        if (this.leaves_obj_type.indexOf(obj_type) == false)
        {
            anc = this.tree.getParentId(anc);
        }

        this.connector.execute(
        {
            _copy: {
                anc: anc,
                node: this.cp_buffer
            }
        });

        if (this.connector.result.nodecopy)
        {
            this.tree.refreshItem(anc);
        }
    },


    delete_obj_treegrid: function (zid, item_id)
    {
        selected = this.tree.getSelectedRowId(true);

        if (selected.length > 1)
        {
            result = confirm(_lang_common['you_really_wish_to_remove_this_objects']);
            item_id = selected;
        }
        else
        {
            result = confirm(_lang_common['you_really_wish_to_remove_this_object']);
        }

        if (result)
        {
            this.connector.execute(
            {
                delete_obj: {
                    id: item_id
                }
            });

            if (this.connector.result.deleted)
            {
                this.tree.deleteSelectedRows();
            }

        }
    },

    delete_obj_grid: function (grid_context, call_delete_func)
    {

        if (!grid_context)
        {
            grid_context = this.gridlist;
        }

        if (!call_delete_func)
        {
            call_delete_func = 'delete_obj';
        }


        if (selected = grid_context.getSelectedId())
        {
            selected = selected.split(',');
        }
        else
        {
            return;
        }

        if (selected.length > 1)
        {
            result = confirm(_lang_common['you_really_wish_to_remove_this_objects']);
        }
        else
        {
            result = confirm(_lang_common['you_really_wish_to_remove_this_object']);
        }

        if (result)
        {
            cdf = {};
            cdf[call_delete_func] = {
                id: selected
            };
            this.connector.execute(cdf);

            if (this.connector.result.deleted)
            {
                for (i = 0; i < selected.length; i++)
                {
                    grid_context.deleteSelectedRows();
                }
            }

        }
    },

    state_restore: function ()
    {

        if (this.current_node && this.current_object_type)
        {
            this.action_by_obj_type(this.current_node, this.current_object_type);
        }
    },



    delete_obj: function (b, item_id)
    {

        selected = this.tree.getSelectedItemId(true);
        if (selected.length > 1)
        {
            result = confirm(_lang_common['you_really_wish_to_remove_this_objects']);
            item_id = selected;
        }
        else
        {
            result = confirm(_lang_common['you_really_wish_to_remove_this_object']);
        }

        if (result)
        {
            this.connector.execute(
            {
                delete_obj: {
                    id: item_id
                }
            });

            if (this.connector.result.deleted)
            {
                selected = this.tree.getSelectedItemId(true)
                for (i = 0; i < selected.length; i++)
                {
                    this.tree.deleteItem(selected[i], false);
                }
                return true;
            }

        }
        else
        {
            return false;
        }
    },



    on_treegrid_drag: function (idNode, idTo, drop)
    {
        this.connector.execute(
        {
            changeAncestorGrid: {
                id: idNode,
                ancestor: idTo,
                relative: drop.dragContext.dropmode
            }
        });

        if (this.connector.result['dragOK'])
        {
            return true;
        }

        return false;
    },

    on_tree_drag: function (idNode, idTo, sibling, idTreeFrom, idTreeTo)
    {
        this.connector.execute(
        {
            changeAncestor: {
                id: idNode,
                ancestor: idTo,
                relative: sibling
            }
        });

        if (this.connector.result['dragOK'])
        {
            return true;
        }

        return false;
    },

    init: function ()
    {
        this.connector = new Connector(this.module_name);
    },

    tree_start_loading: function (_treename)
    {
        this.tree.setXMLAutoLoading("tree_xml_server.php?tree=" + _treename + "_container");
        this.tree.loadXML("tree_xml_server.php?tree=" + _treename + "_container&id=0");
        //globalize                    
        setTimeout("XTR_" + this.module_name + ".tree.loadXML('tree_xml_server.php?tree=" + _treename + "_container&id=1')", 100);
    }
};