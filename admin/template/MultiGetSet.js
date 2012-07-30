// https://gist.github.com/2956493

/**
 July 30th 2012
 Damien "Mistic"Sorel
 add increment() and decrement() methods
 */

var MultiGetSet = function(opt){

    var getType = function(o) {
      return ({}).toString.call(o).match(/\s([a-zA-Z]+)/)[1].toLowerCase()
    };

    if(!opt.public || !opt.private)
        return opt.public;

    if(opt.handler && opt.handler.init)
        opt.handler.init(opt);

    if(!opt.handler || !opt.handler.setter){
        opt.public.set = function(paramName, newValue){
            opt.private[paramName] = newValue;
        };
    }else{
        opt.public.set = function(paramName, newValue){
            return opt.handler.setter({
                public: opt.public,
                private: opt.private,
                paramName: paramName,
                newValue: newValue,
                caller: arguments.callee.caller
            });
        };
    }

    if(!opt.handler || !opt.handler.getter){
        opt.public.get = function(paramName){
            return opt.private[paramName];
        };
    }else{
        opt.public.get = function(paramName){
            return opt.handler.getter({
                public: opt.public,
                private: opt.private,
                paramName: paramName,
                caller: arguments.callee.caller
            });
        };
    }
    
    if(!opt.handler || !opt.handler.adder){
        opt.public.add = function(paramName, newValue){
            if(getType(opt.private[paramName])==="array")
                opt.private[paramName].push(newValue);
        };
    }else{
        opt.public.add = function(paramName, newValue){
            if(getType(opt.private[paramName])==="array")
                return opt.handler.adder({
                    public: opt.public,
                    private: opt.private,
                    paramName: paramName,
                    newValue: newValue,
                    caller: arguments.callee.caller
                });
        };
    }
    
    opt.public.increment = function(paramName, add) {
      if (add == null) add = 1;
      opt.public.set(paramName, opt.public.get(paramName)+add);
    };
    
    opt.public.decrement = function(paramName, rem) {
      if (rem == null) rem = 1;
      opt.public.increment(paramName, -rem);
    };

    return opt.public;
};

var Observable = {


    clone: function(o){
        if(o == null || typeof(o) != 'object')
            return o;

        var temp = o.constructor(); // changed

        for(var key in o)
            temp[key] = Observable.clone(o[key]);
        return temp;
    },

    init: function(opt){
        opt.public.listeners = {};

        opt.public.listen = function(paramName, callback){
            if(Object.prototype.toString.call(paramName) === '[object Array]')
                for(var p in paramName){
                    opt.public.listenOne(paramName[p], callback);
                }
            else
                opt.public.listenOne(paramName, callback);
        };

        opt.public.listenOne = function(paramName, callback){
            if(!opt.public.listeners[paramName])
                opt.public.listeners[paramName] = [];
            opt.public.listeners[paramName].push(callback);
        };
    },

    setter: function(opt){
        if(opt.private[opt.paramName] == opt.newValue)
            return;

        opt.oldValue = Observable.clone(opt.private[opt.paramName]);

        opt.private[opt.paramName] = opt.newValue;

        for(var listener in opt.public.listeners[opt.paramName]){
            if(opt.caller != opt.public.listeners[opt.paramName][listener])
                opt.public.listeners[opt.paramName][listener](opt);
        }
    },

    adder: function(opt){
        if(opt.private[opt.paramName] == opt.newValue || !opt.private[opt.paramName].push)
            return;

        opt.oldValue = Observable.clone(opt.private[opt.paramName]);

        opt.private[opt.paramName].push(opt.newValue);
        opt.newValue = opt.private[opt.paramName];

        for(var listener in opt.public.listeners[opt.paramName]){
            if(opt.caller != opt.public.listeners[opt.paramName][listener])
                opt.public.listeners[opt.paramName][listener](opt);
        }
    }
};