detectLocalStorage = function ()
{
    return ('localStorage' in window) && window[localstorage] !== null;
};



new PeriodicalExecuter(function (pe)
{
    XTR_main.connector.execute(
    {
        ping: true
    }, false, 'route');
}, 300);


if (detectLocalStorage())
{
    var useGSTORAGE = true;
    var GSTORAGE = window.localStorage;

}
else
{
    var useGSTORAGE = false;
}


function getLocaLStorageAsObject()
{
    var i = -1,
        key, len = localStorage.length,
        // the length property tells us 
        // how many items are in the storage
        res = {};
    while (++i < len)
    {
        key = localStorage.key(i); // retrieve the value of each key at each index
        res[key] = localStorage.getItem(key); // retrieve the value using the getItem method
    }
    return res;
}


//var useRunTimeTemplate = true;
var xinha_modal = null;

var XTR_main = null;
var startNode = "r",
    lastSelected = "r";

var tree;

var xRecycle_win = null;
var xFileManager = null;
var xSFU = null;
var PHPSESSID = 1;
var growlerr = new k.Growler();
var xRecycle = null;


var XTRmain = Class.create();

Object.extend(Object.extend(XTRmain.prototype, Abstract.prototype), {
    
    loadedJS: new Array(),
    cache_modules: true,
    connector: new Connector('Adm'),
    editor: null,

    initialize: function ()
    {
        this.current_module = null;
        this.called_modules = new Array();
        this.cached_tpl = new Array();
        this.loadingTimeout = null;
        this.bind_fronted();
    },
    //m
    preload_tpl_from_GS: function ()
    {
        ls = getLocaLStorageAsObject();
        if (ls != null)
        {
            for (var name in ls)
            {
                keys = name.split('@');
                if (keys[0] == 'tpl')
                {
                    a_path = implode(new Array('tpl', keys[1], keys[2]), '@');
                    this.cache_tpl(keys[1], keys[2], String(ls[a_path + '@tpl']), ls[a_path + '@time']);
                }
            }
        }
    },
    
    load_js: function (path)
    {
        if (this.loadedJS)
        {
            if (this.loadedJS[path]) return false;
        }
        var transport = Ajax.getTransport();
        transport.open('POST', path, false);
        transport.send(null);
        var code = transport.responseText;
        var script = document.createElement("script");
        script.text = code;
        document.getElementsByTagName('head')[0].appendChild(script);
        return true;
    },

/*
   * calltype- normal или silent
   */

    switch_main_state: function (switch_to)
    {
        $(switch_to).siblings().invoke('hide');
        $(switch_to).show();
    },
    show_main: function (a)
    {
        if (a.main != true)
        {
            this.switch_main_state('fw_container');
            a.main = true;
        }
        else
        {
            this.switch_main_state('mainwin_container');
            a.main = false;
        }
    },
    load_module: function (module, calltype)
    {
        if (calltype == 'normal')
        {
            this.switch_main_state('mainwin_container');
            if (this.current_module != null)
            {
                this.current_module.destructor();
            }

            v = $$('.tree-cont h1');
            v[0].update(module_name(module));

        }

        xtr_name = "XTR_" + module;
        if (typeof this.called_modules[xtr_name] != 'object')
        {
            //   this.load_js('/modules/'+module+'/js/'+module+'.js');
            run_str = xtr_name + "=new XTR" + module + "();";
            eval(run_str);
            if (calltype == 'normal')
            {
                run_str = xtr_name + ".build_interface();";
                eval(run_str);
                this.current_module = eval("XTR_" + module);
                this.called_modules[xtr_name] = this.current_module;
            }
            else
            {
                //кешируем silent модуль
                this.called_modules[xtr_name] = eval("XTR_" + module);
            }
        }
        else
        {
            if (calltype == 'normal')
            {
                this.current_module = this.called_modules[xtr_name];
                run_str = xtr_name + ".build_interface();";
                eval(run_str);
                this.current_module = eval("XTR_" + module);
            }
        }
    },
    //m
    //m
    set_rightside: function (text)
    {
        $('rightside').update(text);
    },
    set_svside: function (text)
    {
        $('svside').update(text);
    },
    set_rightside_eform: function (text)
    {
        if (arguments[1])
        {
            str = '-' + arguments[1];
        }
        else
        {
            str = '';
        }
        $('rightside').update('<div class="pw-edit' + str + '" id="pw-edit">' + text + '</div>');
    },

    set_window_eform: function (text)
    {
        if (arguments[1])
        {
            str = '-' + arguments[1];
        }
        else
        {
            str = '';
        }
        $('wineform').update('<div class="pw-edit' + str + '">' + text + '</div>');
    },
    /*элементы шаблонной системы*/
    //module,tpl_arr+
    tpl_exists: function (module, tpl_name)
    {
        if ((this.cached_tpl) && (this.cached_tpl[module]) && (this.cached_tpl[module].get(tpl_name)))
        {
            return true;
        }
        else
        {
            return false;
        }
    },
    load_module_tpls: function (module, tpl_arr)
    {
        var tpls = new Array();
        tplstime = new Array();
        if (tpl_arr)
        {
            tpl_arr.each(function (tplobj)
            {
                if (this.tpl_exists(module, tplobj) == false)
                {
                    tpls.push(
                    {
                        tpl_name: tplobj
                    });
                }
                else
                {
                    //проверка актуальности отсутсвующих шаблонов
                    if (useGSTORAGE)
                    {
                        if (!this.cached_tpl[module].get(tplobj).loaded)
                        {
                            tpls.push(
                            {
                                tpl_name: tplobj,
                                time: this.cached_tpl[module].get(tplobj).time
                            });
                        }
                    }
                }
            }.bind(this));
            if (tpls.size() > 0)
            {
                this.connector.execute(
                {
                    load_module_tpls_front: {
                        module: module,
                        tpls: tpls
                    }
                }, false, 'route');
                this.cache_mass_tpl(module, $H(this.connector.lct), $H(this.connector.result.lm_time));
                this.connector.lct = null;
            }
        }
    },
    show_inner_tunes: function ()
    {
        if (arguments[0])
        {
            container = $(arguments[0]);
        }
        else
        {
            container = $('tunescontainer');
        }
        Effect.toggle(container, 'blind', {
            duration: 0.15
        });
    },
    //m
    cache_mass_tpl: function (module, lct, mtime)
    {
        if (lct.size() > 0)
        {
            lct.each(function (pair)
            {
                if (mtime == null)
                {
                    m = 0;
                }
                else
                {
                    m = mtime.get(pair.key);
                }
                this.cache_tpl(module, pair.key, pair.value, m, true)
            }.bind(this)); //зануляем кеш объекта
            lct = null;
        }
        else
        {
            if (mtime)
            {
                mtime.each(function (pair)
                {
                    if (!Object.isUndefined(this.cached_tpl[module].get(pair.key))) this.cached_tpl[module].get(pair.key).loaded = true;
                }.bind(this));
            }
        }
    },
    cache_tpl: function (module, tpl_name, tpl_text, tpl_time, anew_loaded)
    {
        tpl = new Hash();
        if (Object.isUndefined(anew_loaded))
        {
            anew_loaded = false;
        }
        tpl.set(tpl_name, {
            tpl: tpl_text,
            time: tpl_time,
            loaded: anew_loaded
        });
        if (useGSTORAGE)
        {
            storageTplTime = String(GSTORAGE[implode(new Array('tpl', module, tpl_name, 'time'), '@')]);
            if (storageTplTime != tpl_time)
            {
                GSTORAGE[implode(new Array('tpl', module, tpl_name, 'time'), '@')] = tpl_time;
                GSTORAGE[implode(new Array('tpl', module, tpl_name, 'tpl'), '@')] = tpl_text;
            }
        }
        if (!this.cached_tpl[module])
        {
            this.cached_tpl[module] = tpl;
        }
        else
        {
            this.cached_tpl[module].update(tpl);
        }
    },
    //m
    get_tpl: function (module, tpl_name)
    {

        if (arguments[2])
        {
            this.load_module_tpls(module, new Array(tpl_name))
        }

        if (this.cached_tpl[module] && this.cached_tpl[module].get(tpl_name))
        {
            return this.cached_tpl[module].get(tpl_name).tpl;
        }
        else
        {


            this.load_module_tpls(module, new Array(tpl_name))
        }

    },
    show_loading: function ()
    {
        $('loading').show();
    },
    hide_loading: function ()
    {
        $('loading').hide();
    }



    ,
    bind_fronted: function ()
    {

        $$('.front-ed div a').each(function (a)
        {
            a.observe('click', function ()
            {
                if ('enable' == this.readAttribute('rel'))
                {
                    this.previous().className = 'off';
                    this.className = 'on';
                    XTR_main.connector.execute(
                    {
                        enable_fronted: {
                            enable: true
                        }
                    }, false, 'route');

                }
                else
                {
                    this.next().className = 'off';
                    this.className = 'on';
                    XTR_main.connector.execute(
                    {
                        enable_fronted: {
                            enable: false
                        }
                    }, false, 'route');
                }

            });




        });

    }


    ,
    show_site_selector: function ()
    {
        Effect.toggle('siteselector', 'Appear', {
            duration: 0.15
        });
    },
  
    /*result container*/
    set_result: function (message)
    {
        {
            growlerr.info(message, {
                life: 2
            });
        }
    },

    get_modules: function (actionable)
    {
        if (!this.modules)
        {
            this.connector.execute(
            {
                get_module_list: {
                    actionable: actionable
                }
            }, false, 'route');
            this.modules = this.connector.result.modules;
        }

        return this.modules;
    },

    module_search: function ()
    {
        this.current_module.onsearch_handler();
    },

    to_gridholder: function (grid)
    {
        grid.attachToObject($('gridHolder'));

    },

    apply_weditor: function (back_element)
    {
        if (back_element)
        {
            back_element.blur();
            this._back_el = $(back_element);
            this.weditor.showwin();
            XTR_main.dhxWins.window("weditor").bringToTop();
            oEditor = CKEDITOR.instances.editor1;
            if (this._back_el.value)
            {
                oEditor = CKEDITOR.instances.editor1;
                oEditor.setData('');
                oEditor.setData(this._back_el.value);
            }
            else
            {
                oEditor.setData('');

                this.weditor_opened = true;
            }
        }
    },

    start_weditor: function ()
    {

        var config = {
            language: 'ru',
            height: 450
            
        };
        config.toolbar = 'Full';
        config.FirefoxSpellChecker = true,
        config.BrowserContextMenu = true 

        config.toolbar_Full = [
            ['Source', '-', 'Save', 'NewPage', 'Preview', '-', 'Templates'],
            ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Print', 'SpellChecker', 'Scayt'],
            ['Undo', 'Redo', '-', 'Find', 'Replace', '-', 'SelectAll', 'RemoveFormat'],
            ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', '-', 'ShowBlocks'],

            '/', ['Bold', 'Italic', 'Underline', 'Strike', '-'],
            ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'Blockquote', 'CreateDiv'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'], ,


            ['Styles', 'Format', 'Font', 'FontSize'],
            ['TextColor', 'BGColor'],
            ['Link', 'Unlink', 'Anchor', 'Subscript', 'Superscript']

        ];


        this.weditor = XTR_main.dhxWins.createWindow("weditor", 20, 10, 1000, 600, 1);
        this.weditor.setModal(false);
        this.weditor.attachHTMLString('<div id="global_ck"></div>');
        this.weditor.hidewin();
        this.weditor.centerOnScreen();
        this.weditor.setText('');

        this.dhxWins.attachEvent("onHide", function (win)
        {
            if (win.idd == 'weditor')
            {
                oEditor = CKEDITOR.instances.editor1;
                XTR_main._back_el.value = oEditor.getData();
                this.weditor_opened = false;
            }

        });

/*  Control.Modal.container.get('weditor').appendChild(txt_ck);
          Control.Modal.container.get('weditor').appendChild(b_ck); 
          */

          
        CKEDITOR.appendTo('global_ck', config, '');
        this.weditor_opened = false;

    }


});

function module_name(mname)
{
    mlist = {
        fusers: _lang_main['fusers'],
        pages: _lang_main['pages'],
        ishop: _lang_main['ishop'],
        banners: _lang_main['banners'],
        catalog: _lang_main['catalog'],
        forms: _lang_main['forms'],
        templates: _lang_main['templates'],
        news: _lang_main['news'],
        faq: _lang_main['faq'],
        price: _lang_main['price'],
        content: _lang_main['content'],
        gallery: _lang_main['gallery'],
        users: _lang_main['users'],
        votes: _lang_main['votes'],
        search: _lang_main['search'],
        subscribe: _lang_main['subscribe'],
        backup: _lang_main['backup'],
        comments: _lang_main['comments']
    }

    if (!Object.isUndefined(mlist[mname]))
    {
        return mlist[mname];
    }
    else
    {
        return mname;
    }
}


String.prototype.trim = function ()
{

    return this.replace(/(^\s+)|(\s+$)/g, "");

}


function passwordComplexity(input)
{
    $('passwordComplextity').update(this.testPasswordComplex(input.value));
}

function testPasswordComplex(passwd)
{
    var intScore = 0
    var strVerdict = _lang_main['very_poorly']


    // PASSWORD LENGTH
    if (passwd.length < 5) // length 4 or less
    {
        intScore = (intScore + 3)

    }
    else if (passwd.length > 4 && passwd.length < 8) // length between 5 and 7
    {
        intScore = (intScore + 6)

    }
    else if (passwd.length > 7 && passwd.length < 16) // length between 8 and 15
    {
        intScore = (intScore + 12)

    }
    else if (passwd.length > 15) // length 16 or more
    {
        intScore = (intScore + 18)

    }


    if (passwd.match(/[a-z]/)) // [verified] at least one lower case letter
    {
        intScore = (intScore + 1)

    }

    if (passwd.match(/[A-Z]/)) // [verified] at least one upper case letter
    {
        intScore = (intScore + 5)

    }

    // NUMBERS
    if (passwd.match(/\d+/)) // [verified] at least one number
    {
        intScore = (intScore + 5)

    }

    if (passwd.match(/(.*[0-9].*[0-9].*[0-9])/)) // [verified] at least three numbers
    {
        intScore = (intScore + 5)

    }


    // SPECIAL CHAR
    if (passwd.match(/.[!,@,#,$,%,^,&,*,?,_,~]/)) // [verified] at least one special character
    {
        intScore = (intScore + 5)
    }

    // [verified] at least two special characters
    if (passwd.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/))
    {
        intScore = (intScore + 5)
    }

    // COMBOS
    if (passwd.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) // [verified] both upper and lower case
    {
        intScore = (intScore + 2)
    }

    if (passwd.match(/([a-zA-Z])/) && passwd.match(/([0-9])/)) // [verified] both letters and numbers
    {
        intScore = (intScore + 2)

    }

    if (passwd.match(/([a-zA-Z0-9].*[!,@,#,$,%,^,&,*,?,_,~])|([!,@,#,$,%,^,&,*,?,_,~].*[a-zA-Z0-9])/))
    {
        intScore = (intScore + 2)

    }

    if (intScore < 16)
    {
        strVerdict = _lang_main['very_poorly_such_password_is_easy_for_guessing']
    }
    else if (intScore > 15 && intScore < 25)
    {
        strVerdict = _lang_main['poorly_protected']
    }
    else if (intScore > 24 && intScore < 35)
    {
        strVerdict = _lang_main['average_complexity']
    }
    else if (intScore > 34 && intScore < 45)
    {
        strVerdict = _lang_main['really_difficult']
    }
    else
    {
        strVerdict = _lang_main['extreme_difficult']
    }
    return _lang_main['complexity_password'] + ': ' + strVerdict;
}

//глупая функция

function trans_rus_lat(from, to, _length, ignore, extended)
{
    from = document.getElementById(from).value.toLowerCase();
    if (from.length == 0)
    {
        return false;
    }
    to = document.getElementById(to);
    from = from.substring(0, _length);




    to.value = translit(from, ignore)
}



function selector_select(id, optionvalue)
{
    if (sel = document.getElementById(id))
    {
        for (i = 0; i < sel.options.length; i++)
        {

            if (sel.options[i].value == optionvalue)
            {

                sel.options[i].selected = true;
            }
        }
    }
}


function in_array(arr, value)
{
    for (var i in arr)
    {
        if (arr[i] == value) return true;
    }
    return false;
}

function combine_to_hash(keys, values)
{
    _H = $H(
    {});
    for (i = 0; i < keys.size(); i++)
    {
        if (!keys[i].blank())
        {
            _H.set(keys[i], values[i]);
        }
    }
    return _H;
}

function arr_convert_to_hash(arr)
{
    h = new Hash();
    for (key in arr)
    {
        if (typeof arr[key] != 'function')
        {
            h.set(key, arr[key]);
        }
    }
    return h;
}

function implode(arr, delim)
{
    return arr.join(delim);
}

var array_merge = function (arr1, arr2)
    {
        if ((arr1 && (arr1 instanceof Array)) && (arr2 && (arr2 instanceof Array)))
        {
            for (var idx in arr2)
            {
                arr1.push(arr2[idx]);
            }
        }
        else if ((arr1 && (arr1 instanceof Object)) && (arr2 && (arr2 instanceof Object)))
        {
            for (var idx in arr2)
            {
                if (idx in arr1)
                {
                    if (typeof arr1[idx] == 'object' && typeof arr2 == 'object')
                    {
                        arr1[idx] = array_merge(arr1[idx], arr2[idx]);

                    }
                    else
                    {
                        arr1[idx] = arr2[idx];
                    }
                }
                else
                {
                    arr1[idx] = arr2[idx];
                }
            }
        }
        return arr1;
    }


function select_option(select_el, selected_)
{
    i = 0;
    if (!Object.isUndefined(select_el))
    {
        $A(select_el.options).each(function (opt)
        {
            if (opt.value == selected_)
            {
                opt.selected = true;
                select_el.selectedIndex = i;
            }
            i++;
        });
    }
}

function toggle_main_menu(to)
{
    if (to)
    {
        Effect.Fade('mainmenu', {
            duration: 0.15
        });
    }
    else
    {
        Effect.toggle('mainmenu', 'Appear', {
            duration: 0.15
        });
    }
}

function getContinua()
{
    if (typeof getContinua.counter == 'undefined')
    {
        getContinua.counter = 1000;
    }

    return ++getContinua.counter;

}


_select_filter_yes_no = function (id)
{

    switch (id)
    {
    case "1":
        return _lang_main['yes'];
        break;

    case "0":
        return _lang_main['no'];
        break;
    }
}



/*global onload*/

function onLoad()
{
    XTR_main = new XTRmain();
    XTR_main.dhxWins = new dhtmlXWindows();

    if (!FED_MODE)
    {
        XTR_main.dhxWins.setImagePath("xjs/_components/windows/");
    }

    XTR_main.connector.execute(
    {
        getSessionId: true
    }, false, 'route');
    
    PHPSESSID = XTR_main.connector.result.sessionid;

    if (XTR_main.connector.result.clearGSTORAGE)
    {
        GSTORAGE.clear();
    }
    else
    {

        if (useGSTORAGE)
        {
            XTR_main.preload_tpl_from_GS()
        }
    }


    XTR_main.load_module_tpls('admin', new Array('eform_start', 'eform_end'));
    xFileManager = new xMatrixView('xmview', {
        connector: XTR_main.connector
    });


    if (!FED_MODE)
    {
        XTR_main.start_weditor();
        xRecycle = new XTRrecycle();
        Effect.Fade("overlay", {
            duration: 1,
            fps: 1000
        });

    }

}
Event.observe(window, "load", onLoad);
var XTR_STARTED = true;