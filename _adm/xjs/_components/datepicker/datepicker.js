DateFormat = Class.create();
Object.extend(DateFormat, {
    MONTH_NAMES: ['January','February','March','April','May','June','July','August','September','October','November','December','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    DAY_NAMES: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
    LZ: function(x) {return(x<0||x>9?"":"0")+x},
    compareDates: function(date1,dateformat1,date2,dateformat2) {
        var d1=DateFormat.parseFormat(date1,dateformat1);
        var d2=DateFormat.parseFormat(date2,dateformat2);
        if (d1==0 || d2==0) return -1;
        else if (d1 > d2) return 1;
        return 0;
    },
    format: function(date,format) {
        format=format+"";
        var result="";
        var i_format=0;
        var c="";
        var token="";
        var y=date.getYear()+"";
        var M=date.getMonth()+1;
        var d=date.getDate();
        var E=date.getDay();
        var H=date.getHours();
        var m=date.getMinutes();
        var s=date.getSeconds();
        var yyyy,yy,MMM,MM,dd,hh,h,mm,ss,ampm,HH,H,KK,K,kk,k;
        // Convert real date parts into formatted versions
        var value=new Object();
        if (y.length < 4) {y=""+(y-0+1900);}
        value["y"]=""+y;
        value["yyyy"]=y;
        value["yy"]=y.substring(2,4);
        value["M"]=M;
        value["MM"]=DateFormat.LZ(M);
        value["MMM"]=DateFormat.MONTH_NAMES[M-1];
        value["NNN"]=DateFormat.MONTH_NAMES[M+11];
        value["d"]=d;
        value["dd"]=DateFormat.LZ(d);
        value["E"]=DateFormat.DAY_NAMES[E+7];
        value["EE"]=DateFormat.DAY_NAMES[E];
        value["H"]=H;
        value["HH"]=DateFormat.LZ(H);
        if (H==0){value["h"]=12;}
        else if (H>12){value["h"]=H-12;}
        else {value["h"]=H;}
        value["hh"]=DateFormat.LZ(value["h"]);
        if (H>11){value["K"]=H-12;} else {value["K"]=H;}
        value["k"]=H+1;
        value["KK"]=DateFormat.LZ(value["K"]);
        value["kk"]=DateFormat.LZ(value["k"]);
        if (H > 11) { value["a"]="PM"; }
        else { value["a"]="AM"; }
        value["m"]=m;
        value["mm"]=DateFormat.LZ(m);
        value["s"]=s;
        value["ss"]=DateFormat.LZ(s);
        while (i_format < format.length) {
            c=format.charAt(i_format);
            token="";
            while ((format.charAt(i_format)==c) && (i_format < format.length))
                token += format.charAt(i_format++);
            if (value[token] != null) result += value[token];
            else result += token;
        }
        return result;
    },
    _isInteger: function(val) {
        var digits="1234567890";
        for (var i=0; i < val.length; i++)
            if (digits.indexOf(val.charAt(i))==-1) return false;
        return true;
    },
    _getInt: function(str,i,minlength,maxlength) {
        for (var x=maxlength; x>=minlength; x--) {
            var token=str.substring(i,i+x);
            if (token.length < minlength) return null;
            if (DateFormat._isInteger(token)) return token;
        }
        return null;
    },
    parseFormat: function(val,format) {
        val=val+"";
        format=format+"";
        var i_val=0;
        var i_format=0;
        var c="";
        var token="";
        var token2="";
        var x,y;
        var now=new Date();
        var year=now.getYear();
        var month=now.getMonth()+1;
        var date=1;
        var hh=now.getHours();
        var mm=now.getMinutes();
        var ss=now.getSeconds();
        var ampm="";
        
        while (i_format < format.length) {
            // Get next token from format string
            c=format.charAt(i_format);
            token="";
            while ((format.charAt(i_format)==c) && (i_format < format.length))
                token += format.charAt(i_format++);
            // Extract contents of value based on format token
            if (token=="yyyy" || token=="yy" || token=="y") {
                if (token=="yyyy") x=4;y=4;
                if (token=="yy") x=2;y=2;
                if (token=="y") x=2;y=4;
                year=DateFormat._getInt(val,i_val,x,y);
                if (year==null) return 0;
                i_val += year.length;
                if (year.length==2) {
                    if (year > 70) year=1900+(year-0);
                    else year=2000+(year-0);
                }
            } else if (token=="MMM"||token=="NNN") {
                month=0;
                for (var i=0; i<DateFormat.MONTH_NAMES.length; i++) {
                    var month_name=DateFormat.MONTH_NAMES[i];
                    if (val.substring(i_val,i_val+month_name.length).toLowerCase()==month_name.toLowerCase()) {
                        if (token=="MMM"||(token=="NNN"&&i>11)) {
                            month=i+1;
                            if (month>12) month -= 12;
                            i_val += month_name.length;
                            break;
                        }
                    }
                }
                if ((month < 1)||(month>12)) return 0;
            } else if (token=="EE"||token=="E") {
                for (var i=0; i<DateFormat.DAY_NAMES.length; i++) {
                    var day_name=DateFormat.DAY_NAMES[i];
                    if (val.substring(i_val,i_val+day_name.length).toLowerCase()==day_name.toLowerCase()) {
                        i_val += day_name.length;
                        break;
                    }
                }
            } else if (token=="MM"||token=="M") {
                month=DateFormat._getInt(val,i_val,token.length,2);
                if(month==null||(month<1)||(month>12)) return 0;
                i_val+=month.length;
            } else if (token=="dd"||token=="d") {
                date=DateFormat._getInt(val,i_val,token.length,2);
                if(date==null||(date<1)||(date>31)) return 0;
                i_val+=date.length;
            } else if (token=="hh"||token=="h") {
                hh=DateFormat._getInt(val,i_val,token.length,2);
                if(hh==null||(hh<1)||(hh>12)) return 0;
                i_val+=hh.length;
            } else if (token=="HH"||token=="H") {
                hh=DateFormat._getInt(val,i_val,token.length,2);
                if(hh==null||(hh<0)||(hh>23)) return 0;
                i_val+=hh.length;
            } else if (token=="KK"||token=="K") {
                hh=DateFormat._getInt(val,i_val,token.length,2);
                if(hh==null||(hh<0)||(hh>11)) return 0;
                i_val+=hh.length;
            } else if (token=="kk"||token=="k") {
                hh=DateFormat._getInt(val,i_val,token.length,2);
                if(hh==null||(hh<1)||(hh>24)) return 0;
                i_val+=hh.length;hh--;
            } else if (token=="mm"||token=="m") {
                mm=DateFormat._getInt(val,i_val,token.length,2);
                if(mm==null||(mm<0)||(mm>59)) return 0;
                i_val+=mm.length;
            } else if (token=="ss"||token=="s") {
                ss=DateFormat._getInt(val,i_val,token.length,2);
                if(ss==null||(ss<0)||(ss>59)) return 0;
                i_val+=ss.length;
            } else if (token=="a") {
                if (val.substring(i_val,i_val+2).toLowerCase()=="am") ampm="AM";
                else if (val.substring(i_val,i_val+2).toLowerCase()=="pm") ampm="PM";
                else return 0;
                i_val+=2;
            } else {
                if (val.substring(i_val,i_val+token.length)!=token) return 0;
                else i_val+=token.length;
            }
        }
        // If there are any trailing characters left in the value, it doesn't match
        if (i_val != val.length) return 0;
        // Is date valid for month?
        if (month==2) {
            // Check for leap year
            if (((year%4==0)&&(year%100 != 0)) || (year%400==0)) { // leap year
                if (date > 29) return 0;
            } else if (date > 28) {
                return 0;
            }
        }
        if ((month==4)||(month==6)||(month==9)||(month==11))
            if (date > 30) return 0;
        // Correct hours value
        if (hh<12 && ampm=="PM") hh=hh-0+12;
        else if (hh>11 && ampm=="AM") hh-=12;
        var newdate=new Date(year,month-1,date,hh,mm,ss);
        return newdate;
    },
    parse: function(val, format) {
        if (format) {
            return DateFormat.parseFormat(val, format);
        } else {
            var preferEuro=(arguments.length==2)?arguments[1]:false;
            var generalFormats=new Array('y-M-d','MMM d, y','MMM d,y','y-MMM-d','d-MMM-y','MMM d');
            var monthFirst=new Array('M/d/y','M-d-y','M.d.y','MMM-d','M/d','M-d');
            var dateFirst =new Array('d/M/y','d-M-y','d.M.y','d-MMM','d/M','d-M');
            var checkList=[generalFormats,preferEuro?dateFirst:monthFirst,preferEuro?monthFirst:dateFirst];
            var d=null;
            for (var i=0; i<checkList.length; i++) {
                var l=checkList[i];
                for (var j=0; j<l.length; j++) {
                    d=DateFormat.parseFormat(val,l[j]);
                    if (d!=0) return new Date(d);
                }
            }
            return null;
        }
    }
});

DateFormat.prototype = {
    initialize: function(format) { this.format = format; },
    parse: function(value) { return DateFormat.parseFormat(value, this.format); },
    format: function(value) { return DateFormat.format(value, this.format); }
}

Date.prototype.format = function(format) {
    return DateFormat.format(this, format);
}


/*
 * Control.DatePicker
 * 
 * Transforms an ordinary input textbox into an interactive date picker.
 * When the textbox is clicked (or the down arrow is pressed), a calendar
 * appears that the user can browse through and select a date.
 *
 * Features:
 *  - Allows user to specify a date format
 *  - Easy to localize
 *  - Customizable by CSS
 *
 * Written and maintained by Jeremy Jongsma (jeremy@jongsma.org)
 */
if (window.Control == undefined) Control = {};

Control.DatePicker = Class.create();
Control.DatePicker.activePicker = null;
Control.DatePicker.prototype = {
    initialize: function(element, options) {
        this.element = $(element);
        this.i18n = new Control.DatePicker.i18n(options && options.locale ? options.locale : 'ru_RU');
        options = this.i18n.inheritOptions(options);
        options = Object.extend({
                        datePicker: true,
                        timePicker: false
                    }, options || {});

        this.handlers = { onClick: options.onClick,
                onHover: options.onHover,
                onSelect: options.onSelect };

        this.options = Object.extend(options || {}, {
                onClick: this.pickerClicked.bind(this),
                onHover: this.dateHover.bind(this),
                onSelect: this.datePicked.bind(this)
            });

        if (this.options.timePicker && this.options.datePicker)
            this.options.currentFormat = this.options.dateTimeFormat;
        else if (this.options.timePicker)
            this.options.currentFormat = this.options.timeFormat;
        else
            this.options.currentFormat = this.options.dateFormat;
//        this.options.currentFormat = this.options.timePicker ? this.options.dateTimeFormat : this.options.dateFormat;
        this.options.date = DateFormat.parseFormat(this.element.value, this.options.currentFormat);

        // Lazy load to avoid excessive CPU usage with lots of controls on one page
        this.datepicker = null;

        this.originalValue = null;
        this.hideTimeout = null;

        if (this.options.icon) {
            this.element.style.background = 'url('+this.options.icon+') right center no-repeat #FFF';
            // Prevent text writing over icon
            this.element.style.paddingRight = '20px';
        }
        Event.observe(this.element, 'click', this.togglePicker.bindAsEventListener(this));

        this.hidePickerListener = this.delayedHide.bindAsEventListener(this);
        Event.observe(this.element, 'keydown', this.keyHandler.bindAsEventListener(this));
        Event.observe(document, 'keydown', this.docKeyHandler.bindAsEventListener(this));

        this.pickerActive = false;
    },
    tr: function(str) {
        return this.i18n.tr(str);
    },
    delayedHide: function(e) {
        this.hideTimeout = setTimeout(this.hide.bind(this), 100);
    },
    pickerClicked: function() {
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }
        if (this.handlers.onClick)
            this.handlers.onClick();
    },
    datePicked: function(date) {
        this.element.value = DateFormat.format(date, this.options.currentFormat);
        this.element.focus();
        this.hide();
        if (this.handlers.onSelect)
            this.handlers.onSelect(date);
        if (this.element.onchange)
            this.element.onchange();
    },
    dateHover: function(date) {
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }
        if (this.pickerActive) {
            this.element.value = DateFormat.format(date, this.options.currentFormat);
            if (this.handlers.onHover)
                this.handlers.onHover(date);
        }
    },
    togglePicker: function(e) {
        if (this.pickerActive) {
            this.element.value = this.originalValue;
            this.hide();
        } else {
            this.show();
        }
        Event.stop(e);
        return false;
    },
    docKeyHandler: function(e) {
        if (e.keyCode == Event.KEY_ESC)
            if (this.pickerActive) {
                this.element.value = this.originalValue;
                this.hide();
            }

    },
    keyHandler: function(e) {
        switch (e.keyCode) {
            case Event.KEY_ESC:
                if (this.pickerActive)
                    this.element.value = this.originalValue;
            case Event.KEY_TAB:
                this.hide();
                return;
            case Event.KEY_DOWN:    
                if (!this.pickerActive) {
                    this.show();
                    Event.stop(e);
                }
        }
        if (this.pickerActive)
            return false;
    },
    hide: function() {
        if(this.pickerActive && !this.element.disabled) {
            this.datepicker.releaseKeys();
            Element.remove(this.datepicker.element);
            Event.stopObserving(document, 'click', this.hidePickerListener, true);
            this.pickerActive = false;
            Control.DatePicker.activePicker = null;
        }
    },
    scrollOffset: function(element) {
        var valueT = 0, valueL = 0;
        do {
            if (element.tagName == 'BODY') break;
            valueT += element.scrollTop  || 0;
            valueL += element.scrollLeft || 0;
            element = element.parentNode;
        } while (element);
        return Element._returnOffset(valueL, valueT);
    },
    show: function () {
        if (!this.pickerActive) {
            if (Control.DatePicker.activePicker)
                Control.DatePicker.activePicker.hide();
            this.element.focus();
            if (!this.datepicker)
                this.datepicker = new Control.DatePickerPanel(this.options);
            this.originalValue = this.element.value;

            // Find real page position
            /*
            var pos = this.element.cumulativeOffset();
            if (!/MSIE 8/.test(navigator.userAgent)) {
                // IE seems to account for scrollTop in offsetTop already
                var scroll = this.scrollOffset(this.element);
                pos[0] -= scroll[0] + document.body.scrollTop;
                pos[1] -= scroll[1] + document.body.scrollLeft;
            }
            */
            var pos = Position.positionedOffset(this.element);
            var dim = Element.getDimensions(this.element);
            var pickerTop = /MSIE/.test(navigator.userAgent) ? (pos[1] + dim.height) + 'px' : (pos[1] + dim.height - 1) + 'px';
            this.datepicker.element.style.position = 'absolute';
            this.datepicker.element.style.top = pickerTop;
            this.datepicker.element.style.left = pos[0] + 'px';
            this.datepicker.element.style.zIndex = '99';
            this.datepicker.selectDate(DateFormat.parseFormat(this.element.value, this.options.currentFormat));
            this.datepicker.captureKeys();

            this.element.parentNode.appendChild(this.datepicker.element);
            Event.observe(document, 'click', this.hidePickerListener, true);
            this.pickerActive = true;
            Control.DatePicker.activePicker = this;
            this.pickerClicked();
        }
    }
};

Control.DatePicker.i18n = Class.create();
Object.extend(Control.DatePicker.i18n, {
    baseLocales: {
        'us': {
            dateTimeFormat: 'MM-dd-yyyy HH:mm',
            dateFormat: 'MM-dd-yyyy',
            firstWeekDay: 0,
            weekend: [0,6],
            timeFormat: 'HH:mm'
        },
        'ru': {
            dateTimeFormat: 'dd-MM-yyyy HH:mm',
            dateFormat: 'dd-MM-yyyy',
            firstWeekDay: 1,
            weekend: [0,6],
            timeFormat: 'HH:mm'
        },
        'iso8601': {
            dateTimeFormat: 'yyyy-MM-dd HH:mm',
            dateFormat: 'yyyy-MM-dd',
            firstWeekDay: 1,
            weekend: [0,6],
            timeFormat: 'HH:mm'
        }
    },
    createLocale: function(base, lang) {
        return Object.extend(Object.clone(Control.DatePicker.i18n.baseLocales[base]), {'language': lang});
    }
});
Control.DatePicker.i18n.prototype = {
    initialize: function(code) {
        var lang = code.charAt(2) == '_' ? code.substring(0,2) : code;
        var locale = (Control.DatePicker.Locale[code] || Control.DatePicker.Locale[lang]);
        this.opts = Object.clone(locale || {});
        var language = locale ? Control.DatePicker.Language[locale.language] : null;
        if (language) Object.extend(this.opts, language);
    },
    opts: null,
    inheritOptions: function(options) {
        if (!this.opts) this.setLocale('ru_RU');
        return Object.extend(this.opts, options || {});
    },
    tr: function(str) {
        return this.opts && this.opts.strings ? this.opts.strings[str] || str : str;
    }
};

Control.DatePicker.Locale = {};
with (Control.DatePicker) {
    // Full locale definitions not needed if countries use the language default format
    // Datepicker will fallback to the language default; i.e. 'es_AR' will use 'es'
    Locale['ru'] = i18n.createLocale('ru', 'ru');
/*    Locale['en'] = i18n.createLocale('us', 'en');
    Locale['en_GB'] = i18n.createLocale('eu', 'en');
    Locale['en_AU'] = Locale['en_GB'];
    Locale['de'] = i18n.createLocale('eu', 'de');
    Locale['es_iso8601'] = i18n.createLocale('iso8601', 'es');
    Locale['en_iso8601'] = i18n.createLocale('iso8601', 'en');
    Locale['de_iso8601'] = i18n.createLocale('iso8601', 'de');*/
}

Control.DatePicker.Language = {    
    'ru': { 
        months: [_lang_common['January'], _lang_common['February'],_lang_common['March'], _lang_common['April'],_lang_common['May'],
   _lang_common['June'],_lang_common['July'],_lang_common['August'],_lang_common['September'],_lang_common['October'],_lang_common['November'],_lang_common['December']], 
        
        days: [ _lang_common['Mon'],_lang_common['Tue'], _lang_common['Wed'], _lang_common['Thu'], _lang_common['Fri'], _lang_common['Sat'], _lang_common['Sun']],
        strings: { 
            'Now': _lang_common['Now'], 
            'Today': _lang_common['Today'], 
            'Time': _lang_common['Time'], 
            'Exact minutes': _lang_common['Exact_minutes'], 
            'Select Date and Time': _lang_common['Select_Date_and_Time'],
            'Select Time': _lang_common['Select_Time'],
            'Open calendar': _lang_common['Open_calendar']
        }
    }    
};

Control.DatePickerPanel = Class.create();
Object.extend(Control.DatePickerPanel.prototype, {
    initialize: function(options) {
        this.i18n = new Control.DatePicker.i18n(options && options.locale ? options.locale : 'ru');
        options = this.i18n.inheritOptions(options);
        this.options = Object.extend({
                        className: 'datepickerControl',
                        closeOnToday: true,
                        selectToday: true,
                        showOnFocus: false,
                        datePicker: true,
                        timePicker: false,
                        use24hrs: false,
                        firstWeekDay: 0,
                        weekend: [0,6],
                        months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                        days: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']
                    }, options || {});
        // Make sure first weekday is in the correct range
        with (this.options)
            if (isNaN(firstWeekDay*1)) firstWeekDay = 0;
            else firstWeekDay = firstWeekDay % 7;

        this.keysCaptured = false;
        this.calendarCont = null;
        this.currentDate = this.options.date ? this.options.date : new Date();
        this.dayOfWeek = 0;
        this.minInterval = 5;

        this.selectedDay = null;
        this.selectedHour = null;
        this.selectedMinute = null;
        this.selectedAmPm = null;

        this.currentDays = [];
        this.hourCells = [];
        this.minuteCells = [];
        this.otherMinutes = null;
        this.amCell = null;
        this.pmCell = null;

        this.element = this.createPicker();
        this.selectDate(this.currentDate);
    },
    createPicker: function() {
        var elt = document.createElement('div');
        elt.style.position = 'absolute';
        elt.className = this.options.className;
        this.calendarCont = this.drawCalendar(elt, this.currentDate);

        Event.observe(elt, 'click', this.clickHandler.bindAsEventListener(this));
        Event.observe(elt, 'dblclick', this.dblClickHandler.bindAsEventListener(this));
        this.documentKeyListener = this.keyHandler.bindAsEventListener(this);
        if (this.options.captureKeys)
            this.captureKeys();
        
        return elt;
    },
    tr: function(str) {
        return this.i18n.tr(str);
    },
    captureKeys: function() {
        Event.observe(document, 'keydown', this.documentKeyListener, true);
        this.keysCaptured = true;
    },
    releaseKeys: function() {
        Event.stopObserving(document, 'keydown', this.documentKeyListener, true);
        this.keysCaptured = false;
    },
    setDate: function(date) {
        if (date) {
            // Clear container
            while (this.element.firstChild)
                this.element.removeChild(this.element.firstChild);
            this.calendarCont = this.drawCalendar(this.element, date);
        }
    },
    drawCalendar: function(container, date) {
        var calCont = container;
        if (!this.options.datePicker) {
            var calTable =  document.createElement('table');
            calTable.cellSpacing = 0;
            calTable.cellPadding = 0;
            calTable.border = 0;
        } else {
            var calTable = this.createCalendar(date);
        }

        var rowwidth = this.options.use24hrs ? 6 : 7;
        if (this.options.timePicker) {
            var timeTable;
            if (this.options.timePickerAdjacent && this.options.datePicker) {
                var rows = 0;

                var adjTable = document.createElement('table');
                adjTable.cellSpacing = 0;
                adjTable.cellPadding = 0;
                adjTable.border = 0;
                
                row = adjTable.insertRow(0);

                cell = row.insertCell(0);
                cell.vAlign = 'top';
                cell.appendChild(calTable);
                calCont = cell;

                cell = row.insertCell(1);
                cell.style.width = '5px';

                cell = row.insertCell(2);
                cell.vAlign = 'top';
                timeTable = document.createElement('table');
                timeTable.cellSpacing = 0;
                timeTable.cellPadding = 0;
                timeTable.border = 0;
                cell.appendChild(timeTable);

                container.appendChild(adjTable);

                row = timeTable.insertRow(rows++);
                row.className = 'monthLabel';
                cell = row.insertCell(0);
                cell.colSpan = rowwidth;
                cell.innerHTML = this.tr('Time');

                row = timeTable.insertRow(rows++);
                cell = row.insertCell(0);
                cell.colSpan = rowwidth;
                cell.style.height = '1px';

            } else {
                container.appendChild(calTable);
                timeTable = calTable;
                var rows = calTable.rows.length;

                if (this.options.datePicker) {
                    row = timeTable.insertRow(rows++);
                    cell = row.insertCell(0);
                    cell.colSpan = rowwidth;

                    var hr = document.createElement('hr');
                    Element.setStyle(hr, {'color': 'gray', 'backgroundColor': 'gray', 'height': '1px', 'border': '0', 'marginTop': '3px', 'marginBottom': '3px', 'padding': '0'});
                    cell.appendChild(hr);
                }
            }

            var hourrows = this.options.use24hrs ? 4 : 2;
            for (var j = 0; j < hourrows; ++j) {
                row = timeTable.insertRow(rows++);
                for (var i = 0; i < 6; ++i){
                    cell = row.insertCell(i);
                    cell.className = 'hour';
                    cell.width = '14%';
                    cell.innerHTML = (j*6)+i+(this.options.use24hrs?0:1);
                    cell.onclick = this.hourClickedListener((j*6)+i+(this.options.use24hrs?0:1));
                    this.hourCells[(j*6)+i] = cell;
                }
                if (!this.options.use24hrs) {
                    cell = row.insertCell(i);
                    cell.className = 'ampm';
                    cell.width = '14%';
                    if (j) {
                        cell.innerHTML = this.tr('PM');
                        cell.onclick = this.pmClickedListener();
                        this.pmCell = cell;
                    } else {
                        cell.innerHTML = this.tr('AM');
                        cell.onclick = this.amClickedListener();
                        this.amCell = cell;
                    }
                }
            }

            row = timeTable.insertRow(rows++);
            cell = row.insertCell(0);
            cell.colSpan = 6;

            var hr = document.createElement('hr');
            Element.setStyle(hr, {'color': '#CCCCCC', 'backgroundColor': '#CCCCCC', 'height': '1px', 'border': '0', 'marginTop': '2px', 'marginBottom': '2px', 'padding': '0'});
            cell.appendChild(hr);
            cell = row.insertCell(1);

            for (var j = 0; j < (10/this.minInterval); ++j) {
                row = timeTable.insertRow(rows++);
                for (var i = 0; i < 6; ++i){
                    cell = row.insertCell(i);
                    cell.className = 'minute';
                    cell.width = '14%';
                    var minval = ((j*6+i)*this.minInterval);
                    if (minval < 10) minval = '0'+minval;
                    cell.innerHTML = ':'+minval;
                    cell.onclick = this.minuteClickedListener(minval);
                    this.minuteCells[(j*6)+i] = cell;
                }
                if (!this.options.use24hrs) {
                    cell = row.insertCell(i);
                    cell.width = '14%';
                }
            }

            row = timeTable.insertRow(rows++);
            cell = row.insertCell(0);
            cell.style.textAlign = 'right';
            cell.colSpan = 5;
            cell.innerHTML = '<i>'+this.tr('Exact minutes')+':</i>';

            cell = row.insertCell(1);
            cell.className = 'otherminute';
            var otherInput = document.createElement('input');
            otherInput.type = 'text';
            otherInput.maxLength = 2;
            otherInput.style.width = '2em';
            var inputTimeout = null;
            otherInput.onkeyup = function(e) {
                        if (!isNaN(otherInput.value)) {
                            inputTimeout = setTimeout(function() {
                                    this.currentDate.setMinutes(otherInput.value);
                                    this.dateChanged(this.currentDate);
                                }.bind(this), 500);
                        }
                    }.bindAsEventListener(this);
            otherInput.onkeydown = function(e) {
                        if (e.keyCode == Event.KEY_RETURN)
                            if (this.options.onSelect) this.options.onSelect(this.currentDate);
                        if (inputTimeout)
                            clearTimeout(inputTimeout)
                    }.bindAsEventListener(this);
            // Remove event key capture to allow use of arrow keys
            otherInput.onclick = otherInput.select;
            otherInput.onfocus = this.releaseKeys.bindAsEventListener(this);
            otherInput.onblur = this.captureKeys.bindAsEventListener(this);
            this.otherMinutes = otherInput;
            cell.appendChild(otherInput);
            // Padding cell
            if (!this.options.use24hrs)
                cell = row.insertCell(2);

            row = timeTable.insertRow(rows++);
            cell = row.insertCell(0);
            cell.colSpan = rowwidth;

            hr = document.createElement('hr');
            Element.setStyle(hr, {'color': 'gray', 'backgroundColor': 'gray', 'height': '1px', 'border': '0', 'marginTop': '3px', 'marginBottom': '3px', 'padding': '0'});
            cell.appendChild(hr);

            row = timeTable.insertRow(rows++);
            cell = row.insertCell(0);
            cell.colSpan = rowwidth;

            selectButton = document.createElement('input');
            selectButton.type = 'button';
            if (this.options.datePicker)
                selectButton.value = this.tr('Select Date and Time');
            else
                selectButton.value = this.tr('Select Time');
            selectButton.onclick = function(e) {
                        this.options.onSelect && this.options.onSelect(this.currentDate);
                    }.bindAsEventListener(this);
            cell.appendChild(selectButton);

        } else {
            calCont.appendChild(calTable);
        }

        return calCont;

    },
    createCalendar: function(date) {
        this.currentDate = date;
        this.currentDays = [];

        var today = new Date();
        var previousYear = new Date(date.getFullYear() - 1, date.getMonth(), 1)
        var previousMonth = new Date(date.getFullYear(), date.getMonth() - 1, 1)
        var nextMonth = new Date(date.getFullYear(), date.getMonth() + 1, 1)
        var nextYear = new Date(date.getFullYear() + 1, date.getMonth(), 1)

        var row;
        var cell;
        var rows = 0;

        var calTable = document.createElement('table');
        calTable.cellSpacing = 0;
        calTable.cellPadding = 0;
        calTable.border = 0;

        row = calTable.insertRow(rows++);
        row.className = 'monthLabel';
        cell = row.insertCell(0);
        cell.colSpan = 7;
        cell.innerHTML = this.monthName(date.getMonth()) + ' ' + date.getFullYear();

        row = calTable.insertRow(rows++);
        row.className = 'navigation';

        cell = row.insertCell(0);
        cell.className = 'navbutton';
        cell.title = this.monthName(previousYear.getMonth()) + ' ' + previousYear.getFullYear();
        cell.onclick = this.movePreviousYearListener();
        cell.innerHTML = '&lt;&lt;';

        cell = row.insertCell(1);
        cell.className = 'navbutton';
        cell.title = this.monthName(previousMonth.getMonth()) + ' ' + previousMonth.getFullYear();
        cell.onclick = this.movePreviousMonthListener();
        cell.innerHTML = '&lt;';

        cell = row.insertCell(2);
        cell.colSpan = 3;
        cell.className = 'navbutton';
        cell.title = today.getDate() + ' ' + this.monthName(today.getMonth()) + ' ' + today.getFullYear();
        cell.onclick = this.dateClickedListener(today, true);
        if (this.options.timePicker)
            cell.innerHTML = this.tr('Now');
        else
            cell.innerHTML = this.tr('Today');

        cell = row.insertCell(3);
        cell.className = 'navbutton';
        cell.title = this.monthName(nextMonth.getMonth()) + ' ' + nextMonth.getFullYear();
        cell.onclick = this.moveNextMonthListener();
        cell.innerHTML = '&gt;';

        cell = row.insertCell(4);
        cell.className = 'navbutton';
        cell.title = this.monthName(nextYear.getMonth()) + ' ' + nextYear.getFullYear();
        cell.onclick = this.moveNextYearListener();
        cell.innerHTML = '&gt;&gt;';

        row = calTable.insertRow(rows++);
        row.className = 'dayLabel';
        for (var i = 0; i < 7; ++i){
            cell = row.insertCell(i);
            cell.width = '14%';
            cell.innerHTML = this.dayName((this.options.firstWeekDay + i) % 7);
        }
        
        row = null;
        var workDate = new Date(date.getFullYear(), date.getMonth(), 1);
        var day = workDate.getDay();
        var j = 0;

        // Pad with previous month
        if (day != this.options.firstWeekDay) {
            row = calTable.insertRow(rows++);
            row.className = 'calendarRow';
            workDate.setDate(workDate.getDate() - ((day - this.options.firstWeekDay + 7) % 7));
            day = workDate.getDay();
            while (workDate.getMonth() != date.getMonth()) {
                cell = row.insertCell(row.cells.length);
                this.assignDayClasses(cell, 'dayothermonth', workDate);
                cell.innerHTML = workDate.getDate();
                cell.onclick = this.dateClickedListener(workDate);
                workDate.setDate(workDate.getDate() + 1);
                day = workDate.getDay();
            }
        }

        // Display days
        while (workDate.getMonth() == date.getMonth()) {
            if (day == this.options.firstWeekDay) {
                row = calTable.insertRow(rows++);
                row.className = 'calendarRow';
            }
            cell = row.insertCell(row.cells.length);
            this.assignDayClasses(cell, 'day', workDate);
            cell.innerHTML = workDate.getDate();
            cell.onclick = this.dateClickedListener(workDate);
            this.currentDays[workDate.getDate()] = cell;
            workDate.setDate(workDate.getDate() + 1);
            day = workDate.getDay();
        }

        // Pad with next month
        if (day != this.options.firstWeekDay)
            do {
                cell = row.insertCell(row.cells.length);
                this.assignDayClasses(cell, 'dayothermonth', workDate);
                cell.innerHTML = workDate.getDate();
                var thisDate = new Date(workDate.getTime());
                cell.onclick = this.dateClickedListener(workDate);
                workDate.setDate(workDate.getDate() + 1);
                day = workDate.getDay();
            } while (workDate.getDay() != this.options.firstWeekDay);

        return calTable;
    },
    movePreviousMonthListener: function() {
        return function(e) {
                var prevMonth = new Date(
                        this.currentDate.getFullYear(),
                        this.currentDate.getMonth() - 1,
                        this.currentDate.getDate(),
                        this.currentDate.getHours(),
                        this.currentDate.getMinutes());
                if (prevMonth.getMonth() != (this.currentDate.getMonth() + 11) % 12) prevMonth.setDate(0);
                this.selectDate(prevMonth);
            }.bindAsEventListener(this);
    },
    moveNextMonthListener: function() {
        return function(e) {
                var nextMonth = new Date(
                        this.currentDate.getFullYear(),
                        this.currentDate.getMonth() + 1,
                        this.currentDate.getDate(),
                        this.currentDate.getHours(),
                        this.currentDate.getMinutes());
                if (nextMonth.getMonth() != (this.currentDate.getMonth() + 1) % 12) nextMonth.setDate(0);
                this.selectDate(nextMonth);
            }.bindAsEventListener(this);
    },
    moveNextYearListener: function() {
        return function(e) {
                var nextYear = new Date(
                        this.currentDate.getFullYear() + 1,
                        this.currentDate.getMonth(),
                        this.currentDate.getDate(),
                        this.currentDate.getHours(),
                        this.currentDate.getMinutes());
                if (nextYear.getMonth() != this.currentDate.getMonth()) nextYear.setDate(0);
                this.selectDate(nextYear);
            }.bindAsEventListener(this);
    },
    movePreviousYearListener: function() {
        return function(e) {
                var prevYear = new Date(
                        this.currentDate.getFullYear() - 1,
                        this.currentDate.getMonth(),
                        this.currentDate.getDate(),
                        this.currentDate.getHours(),
                        this.currentDate.getMinutes());
                if (prevYear.getMonth() != this.currentDate.getMonth()) prevYear.setDate(0);
                this.selectDate(prevYear);
            }.bindAsEventListener(this);
    },
    dateClickedListener: function(date, timeOverride) {
        var dateCopy = new Date(date.getTime());
        return function(e) {
                if (!timeOverride) {
                    dateCopy.setHours(this.currentDate.getHours());
                    dateCopy.setMinutes(this.currentDate.getMinutes());
                }
                this.dateClicked(dateCopy);
            }.bindAsEventListener(this);
    },
    hourClickedListener: function(hour) {
        return function(e) {
                this.hourClicked(hour);
            }.bindAsEventListener(this);
    },
    minuteClickedListener: function(minutes) {
        return function(e) {
                this.currentDate.setMinutes(minutes);
                this.dateClicked(this.currentDate);
            }.bindAsEventListener(this);
    },
    amClickedListener: function() {
        return function(e) {
                if (this.selectedAmPm == this.pmCell) {
                    this.currentDate.setHours(this.currentDate.getHours()-12);
                    this.dateClicked(this.currentDate);
                }
            }.bindAsEventListener(this);
    },
    pmClickedListener: function() {
        return function(e) {
                if (this.selectedAmPm == this.amCell) {
                    this.currentDate.setHours(this.currentDate.getHours()+12);
                    this.dateClicked(this.currentDate);
                }
            }.bindAsEventListener(this);
    },
    assignDayClasses: function(cell, baseClass, date) {
        var today = new Date();
        Element.addClassName(cell, baseClass);
        if (date.getFullYear() == today.getFullYear() && date.getMonth() == today.getMonth() && date.getDate() == today.getDate())
            Element.addClassName(cell, 'today');
        if (this.options.weekend.include(date.getDay()))
            Element.addClassName(cell, 'weekend');
    },
    monthName: function(month) {
        return this.options.months[month];
    },
    dayName: function(day) {
        return this.options.days[day];
    },
    dblClickHandler: function(e) {
        if(this.options.onSelect)
            this.options.onSelect(this.currentDate);
        Event.stop(e);
    },
    clickHandler: function(e) {
        if(this.options.onClick)
            this.options.onClick();
        Event.stop(e);
    },
    hoverHandler: function(e) {
        if(this.options.onHover)
            this.options.onHover(date);
    },
    keyHandler: function(e) {
        var days = 0;
        switch (e.keyCode){
            case Event.KEY_RETURN:
                if (this.options.onSelect) this.options.onSelect(this.currentDate);
                break;
            case Event.KEY_LEFT:
                days = -1;
                break;
            case Event.KEY_UP:
                days = -7;
                break;
            case Event.KEY_RIGHT:
                days = 1;
                break;
            case Event.KEY_DOWN:
                days = 7;
                break;
            case 33: // PgUp
                var lastMonth = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, this.currentDate.getDate());
                days = -this.getDaysOfMonth(lastMonth);
                break;
            case 34: // PgDn
                days = this.getDaysOfMonth(this.currentDate);
                break;
            case 13: // enter-key (forms without submit buttons)
                this.dateClicked(this.currentDate);
                break;
            default:
                return;
        }
        if (days != 0) {
            var moveDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), this.currentDate.getDate() + days);
            moveDate.setHours(this.currentDate.getHours());
            moveDate.setMinutes(this.currentDate.getMinutes());
            this.selectDate(moveDate);
        }
        Event.stop(e);
        return false;
    },
    getDaysOfMonth: function(date) {
        var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        return lastDay.getDate();
    },
    getNextMonth: function(month, year, increment) {
        if (p_Month == 11) return [0, year + 1];
        else return [month + 1, year];
    },
    getPrevMonth: function(month, year, increment) {
        if (p_Month == 0) return [11, year - 1];
        else return [month - 1, year];
    },
    dateClicked: function(date) {
        if (date) {
            if (!this.options.timePicker && this.options.onSelect)
                this.options.onSelect(date);
            this.selectDate(date);
        }
    },
    dateChanged: function(date) {
        if (date) {
            if ((!this.options.timePicker || !this.options.datePicker) && this.options.onHover)
                this.options.onHover(date);
            this.selectDate(date);
        }
    },
    hourClicked: function(hour) {
        if (!this.options.use24hrs) {
            if (hour == 12) {
                if (this.selectedAmPm == this.amCell)
                    hour = 0;
            } else if (this.selectedAmPm == this.pmCell) {
                hour += 12;
            }
        }
        this.currentDate.setHours(hour);
        this.dateClicked(this.currentDate);
    },
    selectDate: function(date) {
        if (date) {
            if (this.options.datePicker) {
                if (date.getMonth() != this.currentDate.getMonth()
                    || date.getFullYear() != this.currentDate.getFullYear())
                    this.setDate(date);
                else
                    this.currentDate = date;
                
                if (date.getDate() < this.currentDays.length) {
                    if (this.selectedDay)
                        Element.removeClassName(this.selectedDay, 'current');
                    this.selectedDay = this.currentDays[date.getDate()];
                    Element.addClassName(this.selectedDay, 'current');
                }
            }

            if (this.options.timePicker) {
                var hours = date.getHours();
                if (this.selectedHour)
                    Element.removeClassName(this.selectedHour, 'current');
                if (this.options.use24hrs)
                    this.selectedHour = this.hourCells[hours];
                else
                    this.selectedHour = this.hourCells[hours % 12 ? (hours % 12) - 1 : 11];
                Element.addClassName(this.selectedHour, 'current');

                if (this.selectedAmPm)
                    Element.removeClassName(this.selectedAmPm, 'current');
                this.selectedAmPm = (hours < 12 ? this.amCell : this.pmCell);
                Element.addClassName(this.selectedAmPm, 'current');

                var minutes = date.getMinutes();
                if (this.selectedMinute)
                    Element.removeClassName(this.selectedMinute, 'current');
                Element.removeClassName(this.otherMinutes, 'current');
                if (minutes % this.minInterval == 0) {
                    this.otherMinutes.value = '';
                    this.selectedMinute = this.minuteCells[minutes / this.minInterval];
                    Element.addClassName(this.selectedMinute, 'current');
                } else {
                    this.otherMinutes.value = minutes;
                    Element.addClassName(this.otherMinutes, 'current');
                }
            }

            if (this.options.onHover)
                this.options.onHover(date);
        }
    }
});
