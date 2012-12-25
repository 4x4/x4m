/*
---

name: Router

license: MIT-style license.

authors: Radovan Lozej

provides: [Router]

...
*/

/*
inspiration:
http://documentcloud.github.com/backbone/#Router
Element.Events.hashchange by http://github.com/greggoryhz/MooTools-onHashChange-Event/
_normalize by https://github.com/visionmedia/express 
*/


// Router

var Router = new Class({
    
    Implements: [Options, Events],
    
    options: {
        triggerOnLoad : true // check route on load
    },
    
    routes: {
        // '#!path/:query/:id?': 'method',
    },
    
    initialize: function (options) {
        var self = this
        
        window.onhashchange=function(e){
            
            var hash = location.hash;
            var path = hash.split('?')[0]
            var query = hash.split('?')[1] || '';
            var notfound = true;
            for(route in self.routes) {
                var keys = []
                var regex = self._normalize(route,keys,true,false)
                var found = regex.exec(path)
                
           
                if(found){
                    notfound = false;
                    self.req = found[0]
                    var args = found.slice(1)
                    var param = {}
                    Array.each(args,function(a,i){
                        if(keys[i]) param[keys[i].name] = a
                    });
                    self.route = route;
                    self.param = param || {};
                    self.query = query ? query.parseQueryString() : {};
                    self.before()
                    if(self[self.routes[route]]) self[self.routes[route]]()
                    else alert('"'+self.routes[route]+'" route method is missing, define it')
                    self.after()
                } 
            }
            if(notfound) self.notfound()

        }
        this.init();
        //here some jquery
        if(this.options.triggerOnLoad) $(window).trigger('hashchange');
    },
    
    init: function(){},
    before: function(){},
    after: function(){},
    notfound: function(){},
    
    navigate: function(route,trigger){
           //here some jquery
        if(location.hash == route && trigger) $(window).trigger('hashchange');
        else location.hash = route;
    },
    
    _normalize : function (path, keys, sensitive, strict) {
        if (path instanceof RegExp) return path;
        path = path
        .concat(strict ? '' : '/?')
        .replace(/\/\(/g, '(?:/')
        .replace(/(\/)?(\.)?:(\w+)(?:(\(.*?\)))?(\?)?/g, function(_, slash, format, key, capture, optional){
            keys.push({ name: key, optional: !! optional });
            slash = slash || '';
            return ''
                + (optional ? '' : slash)
                + '(?:'
                + (optional ? slash : '')
                + (format || '') + (capture || (format && '([^/.]+?)' || '([^/]+?)')) + ')'
                + (optional || '');
        })
        .replace(/([\/.])/g, '\\$1')
        .replace(/\*/g, '(.*)');
        return new RegExp('^' + path + '$', sensitive ? '' : 'i');
    }
    
});



