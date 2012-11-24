var ishopModule = x3mModule.extend(
{
    connector: null,
    preventJquery: false,
    constructor: function (preventJquery)
    {
        this.preventJquery = preventJquery;
        this.connector = new fConnector('ishop');
    },

    setPaySystem: function (paysystem)
    {
        this.connector.execute(
        {
            set_paysystem: {
                paysystem: paysystem
            }
        });
    },

    addToCart: function (id, count, ext, callback)
    {
        if (!id) return;
        obj = {
            id: id,
            count: count
        }
        if (ext)
        {
            obj['ext'] = ext;
        }

        this.connector.execute(
        {add_to_cart: obj}, callback);
        if (!callback) return this.connector.result.cart;
    },

    getCartItems: function ()
    {
        this.connector.execute(
        {
            get_cart: true
        });
        return ishop.connector.result.cart_items;
    },

    jqueryRun: function ()
    {
        if (this.preventJquery) return;

/*   $('a[rev=tocart]').ishopToBasket();
    
    */

        $.fn.ishopToBasket = function (options)
        {
            var defaults = {
                count: 1,
                idAttribute: 'id',
                onGoodAdded: null,
                extDataFunc: null,
                syncAddToBasket: true,
                basketContainer: '.basket-container',
                basketCountSelector: '.basket-count',
                basketAllCountSelector: '.basket-count',
                basketSumSelector: '.basket-sum',
                basketElementTemplate: '<li><a>{details.props.Name}</a> <span>{count}</span> <span>{details.props.price}</span> <span>{priceSum}</span></li>'
            };

            var options = $.extend(defaults, options);


            $(this).click(function (e)
            {
                e.preventDefault();
                ext = {};
                if (jQuery.isFunction(options.extDataFunc)) ext = options.extDataFunc(this);

                cart = ishop.addToCart($(this).attr(options.idAttribute), options.count, ext, options.onGoodAdded);

                if (!options.onGoodAdded)
                {
                    $(options.basketCountSelector).html(cart.count);
                    $(options.basketAllCountSelector).html(cart.allcount);
                    $(options.basketSumSelector).html(cart.sum);


                    if (options.syncAddToBasket)
                    {
                        items = ishop.getCartItems()
                        var container = $(options.basketContainer);
                        container.html('');
                        $.each(items, function (i, item)
                        {
                            container.append($.nano(options.basketElementTemplate, item))
                        });
                    }

                }
            });

        }

    }

});


var ishop = new ishopModule();
x3m.addModule('ishop', ishop);