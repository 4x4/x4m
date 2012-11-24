var catalogModule = x3mModule.extend(
{
    connector: null,
    preventJquery: false,
    constructor: function (preventJquery)
    {
        this.preventJquery = preventJquery;
        this.connector = new fConnector('catalog');
    },

    addComparse:function(id)
    {
        this.connector.execute({add_comparse:{id:id}});
    },
    
    jqueryRun: function ()
    {
        if (this.preventJquery) return;
    
          $.fn.catalogAddComparse = function (options)
          {
            var defaults = 
            {
                idAttribute: 'id',
                onAdded: null,
                currentCount:0,
                maxCount:null,
                onMaxCount:null,
                countContainer: '.countComparse'
            };

            var options = $.extend(defaults, options);
          
            $(this).click(function (e)
            {
                
                if(options.maxCount<=options.currentCount)
                {
                    if(options.onMaxCount)
                    {
                            options.onMaxCount($(this));
                    }    
                }
                
                e.preventDefault();
                catalog.addComparse($(this).attr(options.idAttribute));         
                
                options.currentCount=catalog.connector.result.count;
                       
                $(options.countContainer).html(catalog.connector.result.count);
                if(options.onAdded){options.onAdded($(this));}
                
            });

        }

    }

});

//('ds').catalogAddComparse({maxCount:5,onMaxCount:function(){alert('max!')}});

var catalog = new catalogModule();
x3m.addModule('catalog', catalog);