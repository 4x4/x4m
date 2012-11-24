var XTRtemplates = Class.create();
var XTR_templates;

XTRtemplates.prototype = Object.extend(new _modulePrototype(), {
    initialize: function () {
        this.destructable = true;
        this.module_name = 'templates';
        this.current_tpl_id = null;
        this.leaves_obj_type = new Array('_TEMPLATE');
        this.init();
    },

    tree_object_clicked: function (itemid) {
        object_type = this.tree.getNodeParam(itemid, 'obj_type');

        switch (object_type) {
        case "_TGROUP":
            this.show_tpl_slotz(itemid);


            break;

        case "_TEMPLATE":

            this.show_edit_template(itemid);

            break;


        default:
            return false;
        }
    },


    save_template: function (tpl_id) {
        t = xoad.html.exportForm('template_edit');
        t.tplbody = tplbody.getCode();

        this.connector.execute({
            save_template: t
        });

        if (this.connector.result.saved) {
            XTR_main.set_result(_lang_templates['template_saved']);
            this.tree.refreshItem(this.tree.getParentId(this.current_tpl_id));
        } else {

            XTR_main.set_result(this.connector.error);
        }


    },



    show_tpl_slotz: function (itemId) {

        this.connector.execute({
            edit_slotz_alias: {
                tpl_id: itemId
            }
        });
        XTR_main.set_rightside_eform(this.connector.result.slot_alias);

    },


    save_slotz_alias: function () {

        tpl_slotz = xoad.html.exportForm('tpl_slotz');
        this.connector.execute({
            save_slotz_alias: {
                slotz: tpl_slotz
            }
        });
        XTR_main.set_result(_lang_templates['aliases_success_saved']);


    },

    show_edit_template: function (itemId) {


        XTR_main.load_module_tpls(this.module_name, new Array('edit_template'));

        this.connector.execute({
            load_template: {
                tpl_id: itemId
            }
        })
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_template'));
        if (itemId.endsWith('_index.html')) {
            $('tplalias').show();
        }

        xoad.html.importForm('template_edit', this.connector.result.tpldata);
        CodePress.run();
        this.current_tpl_id = itemId;

    },
    //m
    build_interface: function () {
        toggle_main_menu(true);
        if (!this.tree) {
            $('tp-tree-window').appendChild(new Element('div', {
                id: this.module_name + "_treebox",
                className: 'treebox'
            }));
            this.tree = new dhtmlXTreeObject(this.module_name + "_treebox", 'auto', 'auto', "0", "xres/ximg/tree/");
            this.tree.setImagePath("/xres/ximg/green/");
            this.tree.setDataMode("json");
            this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this));
            this.tree.enableMultiselection(1);

            this.tree.setXMLAutoLoading("tree_xml_server.php?tree=template_container");
            this.tree.loadJSON("tree_xml_server.php?tree=template_container&id=0", function ()
            {
                this.tree.refreshItem(1);

            }.bind(this));

        } else {

            $(this.module_name + "_treebox").show();

        }

    }, destructor: function () {

        XTR_main.set_rightside_eform();
        $(this.module_name + "_treebox").hide();
    }




});