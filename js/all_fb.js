 /**************  lib/prelude.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/*
 * The contents of prelude.js (stripped of comments and the such) will be
 * included inline within the <head> of each page by start_html.
 *
 * -- CANNOT @PROVIDE OR @REQUIRE ANYTHING --
 */


/**
 * Primitive, dependentless version of onloadRegister, so that onload
 * handlers can get queued up before we make it to *any* script includes.
 *
 * @author jrosenstein
 */
window.onloadRegister = window.onloadRegister ||
  function(h) { window.onloadhooks.push(h); };
window.onloadhooks = window.onloadhooks || [];


/**
 * All DOM handlers in the generated page (e.g. onclick) will get wrapped in:
 *
 *   onXXX="return wait_for_load(this, event, function() { ... });"
 *
 * The effect is that, if the user tries to interact with the element before
 * the document has loaded, then the interaction will either be ignored or,
 * if we can do so reliably, deferred until all script files are done loading.
 *
 * @param element    The element on which the event fired.
 * @param e          The window.event at the time of the event firing.
 * @param f          The (unbound) handler the user wants executed.
 *
 * @author jrosenstein
 */
window.wait_for_load = window.wait_for_load ||
function (element, e, f) {
  f = bind(element, f, e);
  if (window.loading_begun) {
    return f();
  }

  switch ((e || event).type) {

    case 'load':
      onloadRegister(f);
      return;

    case 'click':
      // Change the cursor to give the user some feedback to wait.
      if (element.original_cursor === undefined) {
        element.original_cursor = element.style.cursor;
      }
      if (document.body.original_cursor === undefined) {
        document.body.original_cursor = document.body.style.cursor;
      }
      element.style.cursor = document.body.style.cursor = 'progress';

      onloadRegister(function() {
        element.style.cursor = element.original_cursor;
        document.body.style.cursor = document.body.original_cursor;
        element.original_cursor = document.body.original_cursor = undefined;

        if (element.tagName.toLowerCase() == 'a') {

          // Simulate calling the onclick handler.  Don't re-use f, since
          // the onclick handler could have changed (e.g. via LinkController).
          var original_event = window.event;
          window.event = e;
          var ret_value = element.onclick.call(element, e);
          window.event = original_event;

          // If onclick didn't return false, follow the link.
          if (ret_value !== false && element.href) {
            window.location.href = element.href;
          }

        } else if (element.click) {
          // For form elements (and more in IE).
          element.click();
        }
      });
      break;

  }

  return false;
};


/**
 *  Returns a function which binds the parameter object and method together.
 *
 *  Bind takes two arguments: an object (optionally, null), and a function
 *  (either the explicit function itself, or the name of a function). It binds
 *  them together and returns a function which, when called, calls the passed
 *  function with the passed object bound as `this'. That is, the following
 *  are nearly equivalent (but see below):
 *
 *    obj.method();
 *
 *    var fn2 = bind(obj, 'method');   // Late binding, see below.
 *    fn2();
 *
 *    var fn3 = bind(obj, obj.method); // Early binding, see below.
 *    fn3();
 *
 *  Binding can occur either by name (as with fn2) or by explicit method (as
 *  with fn3). When binding by name, the binding is "late" and resolved at call
 *  time, NOT at bind time:
 *
 *    function A() { return this.name + ' says "A".'; }
 *    function B() { return this.name + ' says "B".'; }
 *
 *    var obj = { name: 'zebra', f: A };
 *
 *    var earlyBind = bind(obj, f);   // Passing method = early binding
 *    var lateBind  = bind(obj, 'f'); // Passing string = late binding
 *
 *    earlyBind(); // A zebra says "A".
 *    lateBind();  // A zebra says "A".
 *
 *    obj.f = B;
 *
 *    earlyBind(); // A zebra says "A".
 *    lateBind();  // A zebra says "B".
 *
 *  One principle advantage of late binding is that you can late-bind an event
 *  handler, and change it without breaking the bindings.
 *
 *  Note that, because late binding isn't resolved until call time, it can also
 *  fail at call time.
 *
 *    var badLateBind = bind({ f: 42 }, 'f');
 *    badLateBind(); // Fatal error, can't call an integer.
 *
 *  Also note that you can not late bind a global function if you provide an
 *  object. This is a design decision that probably has arguments both ways,
 *  but forcing object bindings to always bind within object scope means global
 *  scope can't accidentally bleed into an object, which could be extremely
 *  astonishing.
 *
 *  Additionally, bind() can curry (purists might argue that this is actually
 *  "partial function application", but they can die in a well fire). Currying
 *  binds arguments to the return function:
 *
 *    function add(a, b) { return a + b; }
 *    var add3 = bind(null, add, 3);
 *    add3(4);                  // 7
 *    add3(5);                  // 8
 *    bind(null, add, 2, 3)();  // 5
 *
 *  bind() is also available as a member of Function:
 *
 *    var fn = function() { }.bind(obj);
 *
 *  This version of bind() can also curry, but it is impossible to perform late
 *  binding this way. For this reason, you may prefer to use the functional
 *  form of bind(), but you should prefer early binding (which catches errors
 *  sooner) to late binding (which may miss them) unless you actually need late
 *  binding (e.g., for event handlers).
 *
 *  bind() can be difficult to understand, particularly if you are not familiar
 *  with functional programming. However, it is worth understanding because it
 *  is awesomely powerful. bind() is the solution to every piece of code which
 *  looks like this:
 *
 *    // Everyone does this at first, but it's bad! Don't do it!
 *    var localCopyOfThis = this;
 *    this.onclick = function(event) {
 *      localCopyOfThis.doAction(event);
 *    }
 *
 *  Clearly, this is hacky, but it's not obvious how to do this better. The
 *  solution is:
 *
 *    this.onclick = this.doAction.bind(this);
 *
 *  @param obj|null An object to bind.
 *  @param function|string A function or method to bind, early or late.
 *  @param any... Zero or more arguments to curry.
 *
 *  @return function A function which, when called, calls the method with object
 *                   and arguments bound.
 *
 *  @author epriestley
 */
window.bind = window.bind ||
function (obj, method /*, arg, arg, arg*/) {

  var args = [];
  for (var ii = 2; ii < arguments.length; ii++) {
    args.push(arguments[ii]);
  }

  return function() {
    var _obj = obj || this;

    var _args = args.slice(); // copy
    for (var jj = 0; jj < arguments.length; jj++) {
      _args.push(arguments[jj]);
    }

    if (typeof(method) == "string") {
      if (_obj[method]) {
        return _obj[method].apply(_obj, _args);
      }
    } else {
      return method.apply(_obj, _args);
    }
  }

};



  /**************  lib/util/bootloader.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  "Bootload" resources into a page dynamically.
 *
 *  @author   epriestley
 *  @provides bootloader, copy-properties
 */

if (!window.Bootloader) {

  /**
   *  Copy properties from one object into another. This is a shallow copy.
   *
   *  Note: This is a `core' function which may be used when first evaluating
   *  other JS files -- in other words, it may be called in global scope.
   *  Eventually we may want to define a core.js defining this and other
   *  functions that are guaranteed to be available before other files are
   *  evaluated.
   *
   *  @author epriestley
   */
  window.copy_properties = function(u, v) {
    for (var k in v) {
      u[k] = v[k];
    }

    //  IE ignores `toString' in object iteration; make sure it gets copied if
    //  it exists. See:
    //    http://webreflection.blogspot.com/2007/07/
    //      quick-fix-internet-explorer-and.html

    //  Avoid a `ua' dependency since we can slip through by just doing
    //  capability detection.
    if (v.hasOwnProperty && v.hasOwnProperty('toString') &&
        (v.toString !== undefined) && (u.toString !== v.toString)) {
      u.toString = v.toString;
    }

    return u;
  }

  /**
   *  Bootload external resources programatically. Bootloader is tightly
   *  integrated with Haste and AsyncRequest.
   */
  window.Bootloader = {


    /**
     *  Load an external CSS or JS resource into the document. This function
     *  takes an object as an argument, which needs `type' and `src' properties
     *  at a minimum:
     *
     *    Bootloader.loadResource({type:'js-ext', src:'/js/meow.js'});
     *
     *  You may also provide a `name' property; if a resource is named, it will
     *  not be loaded if a resource of the same name has already been loaded.
     *
     *  Loading resources is NOT synchronous! You need to use Bootloader.wait()
     *  to register a callback if you are loading resources that are required to
     *  continue execution.
     *
     *  You alo can't wait() on arbitrary resources. You can never wait() on CSS
     *  and can only wait on Javascript if it calls Bootloader.done() to notify
     *  Bootloader that loading has completed. All Javascript loaded through
     *  `rsrc.php' will be properly annotated, but random Javascript "in the
     *  wild" won't work. The reason for this restriction is that Safari 2
     *  doesn't offer any automatic mechanism to detect that a script has
     *  loaded (which is unfortunate, because everything else does).
     *
     *  In general, Bootloader is automatically called by higher-level
     *  abstractions like Haste and AsyncResponse and you should not need to
     *  call it directly unless your use case is unusual. An example of an
     *  unusual but legitimate use case is reCAPTCHA, which does transport via a
     *  JSONP mechanism. You can't wait() for such a resource, but you can
     *  loadResource() it and any callbacks it executes will end up running.
     *
     *  Bootloader can load three types of resources: `js' (Facebook Javascript
     *  served through rsrc.php that calls done() and can be wait()'ed on),
     *  `js-ext' (external Javascript that does not call done() and thus can not
     *  be wait()'ed on; most likely the only use case for this is JSONP), and
     *  `css' (Facebook or external CSS, which can never be wait()'ed on).
     *
     *  @param    obj   Dictionary of type, source, and (optionally) name.
     *  @returns  void
     *
     *  @author   epriestley
     */
    loadResource : function(rsrc) {
      //  We're a bit paranoid about making sure we reference the master
      //  Bootloader on `window'; this isn't really necessary but we stick
      //  Bootloader into some unusual scopes.

      var b = window.Bootloader;

      if (rsrc.name) {
        if (b._loaded[rsrc.name]) {
          return;
        }
        b.markResourcesAsLoaded([rsrc.name]);
      }

      var tgt = b._getHardpoint();

      switch (rsrc.type) {
        case 'js':
          ++b._pending;
        case 'js-ext':
          var script = document.createElement('script');
            script.src  = rsrc.src;
            script.type = 'text/javascript';
          tgt.appendChild(script);
          break;

        case 'css':
          var link  = document.createElement('link');
            link.rel    = "stylesheet";
            link.type   = "text/css";
            link.media  = "all"
            link.href   = rsrc.src;
          tgt.appendChild(link);
          break;
      }
    },


    /**
     *  Register a callback for invocation when resources load. If there are
     *  no pending resources, the callback will be invoked immediately. See
     *  loadResource() for more discussion about the capabilities and
     *  limitations of this mechanism.
     *
     *  @param    function    Callback to invoke when all pending Facebook
     *                        Javascript resources finish loading.
     *  @returns  void
     *
     *  @author   epriestley
     */
    wait : function(wait_fn) {
      var b = window.Bootloader;
      if (b._pending > 0) {
        b._wait.push(wait_fn);
      } else {
        if (b._pending < 0 && window.Util) {
            Util.error('Bootloader- there are supposedly ' + b._pending + ' resources pending.');
        }
        wait_fn();
      }
    },


    /**
     *  Notify Bootloader that a script has loaded. You should probably never
     *  call this directly.
     *
     *  @param    int         Number of scripts which have loaded. Normally,
     *                        this number is 1, but may be larger if invoked by
     *                        a JIT package.
     *  @returns  void
     *
     *  @author   epriestley
     */
    done : function(num) {
      num = num || 1;
      var b = window.Bootloader;
      if (!b._ready) {
        return;
      }

      b._pending -= num;

      if (b._pending <= 0) {
        if (b._pending < 0 && window.Util) {
          Util.error('Bootloader- there are supposedly ' + b._pending + ' resources pending.');
        }
        var wait = b._wait;
        b._wait = [];
        for (var ii = 0; ii < wait.length; ii++) {
          wait[ii]();
        }
      }
    },


    /**
     *  Marks resources as already loaded (for instance, because they are in
     *  "script" tags in the page's source). If you pull in a resource without
     *  using Bootloader but do not mark it as "loaded" and Bootloader is later
     *  instructed to load it, Bootloader won't be able to detect that the
     *  resource is already loaded and the load event will never fire, so
     *  Bootloader will wait for it forever. You should probably never call
     *  this directly, it is invoked automatically by Haste.
     *
     *  @param    array   List of resource names to consider loaded.
     *  @returns  void
     *
     *  @author   epriestley
     */
    markResourcesAsLoaded : function(resources) {
      var b = window.Bootloader;
      for (var ii = 0; ii < resources.length; ii++) {
        b._loaded[resources[ii]] = true;
      }
      b._ready = true;
    },


/* -(  Implementation  )----------------------------------------------------- */


    _getHardpoint : function() {
      var b = window.Bootloader;

      if (!b._hardpoint) {
        var n, heads = document.getElementsByTagName('head');
        if (heads.length) {
          n = heads[0];
        } else {
          n = document.body;
        }
        b._hardpoint = n;
      }

      return b._hardpoint;
    },

    _loaded     : {},
    _pending    : 0,
    _hardpoint  : null,
    _wait       : [],
    _ready      : false

  };
}



  /**************  lib/type/array.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  We implement a bunch of enhancements to Array.prototype, many of which are
 *  defined in the quasi-official "JavaScript 1.6" specification here:
 *
 *    http://developer.mozilla.org/en/docs/New_in_JavaScript_1.6
 *
 *  Specifically, we offer implementations of Array.map(), Array.forEach() (also
 *  aliased as Array.each()), Array.filter(), Array.every(), Array.some(),
 *  and Array.indexOf().
 *
 *  This is `quasi-official' because Mozilla owns JavaScript but browsers
 *  implement whatever they want, and these extensions have yet to be officially
 *  recognized by ECMA or implemented in JScript, etc. Basically, there are
 *  native implementations available in Firefox which we could fall back to for
 *  speed, and other browsers may implement these methods natively in the
 *  future.
 *
 *  These enhancments are only "mostly" compatible with the JavaScript 1.6
 *  specification; they will raise a TypeError if called with `window' bound as
 *  `this' as a security enhancement for FBJS, and will allocate return values
 *  using `this.alloc(N)', not "new Array(N)" which means the return type
 *  depends on the caller. Other deviations (which are minor) are noted below.
 *
 *  These implementations are not optimized.
 *
 *  @author   epriestley
 *  @provides array-extensions
 */


/**
 *  If a class psuedo-extends Array, it can overload this method to make all the
 *  Array extensions that return arrays return objects of the subclass instead.
 *  See List for a more concrete example of this.
 */
Array.prototype.alloc = function(length) {
  return length ? new Array(length) : [];
}


/**
 *  This function conforms to the JavaScript 1.6 specification.
 */
Array.prototype.map = function(callback, thisObject) {
  if (this == window) {
    throw new TypeError();
  }

  if (typeof(callback) !== "function") {
    throw new TypeError();
  }

  var ii;
  var len = this.length;
  var r   = this.alloc(len);
  for (ii = 0; ii < len; ++ii) {
    if (ii in this) {
      r[ii] = callback.call(thisObject, this[ii], ii, this);
    }
  }

  return r;
};


/**
 *  This function deviates from the Javascript 1.6 specification: it returns
 *  the calling array, not void.
 */
Array.prototype.forEach = function(callback, thisObject) {
  this.map(callback, thisObject);
  return this;
};


/**
 *  This function deviates from the Javascript 1.6 specification: it returns
 *  the calling array, not void.
 */
Array.prototype.each    = function(callback, thisObject) {
  return this.forEach.apply(this, arguments);
}


/**
 *  This function conforms to the JavaScript 1.6 specification.
 */
Array.prototype.filter = function(callback, thisObject) {
  if (this == window) {
    throw new TypeError();
  }

  if (typeof(callback) !== "function") {
    throw new TypeError();
  }

  var ii, val, len = this.length, r = this.alloc();
  for (ii = 0; ii < len; ++ii) {
    if (ii in this) {
      //  Specified, to prevent mutations in the original array.
      val = this[ii];
      if (callback.call(thisObject, val, ii, this)) {
        r.push(val);
      }
    }
  }

  return r;
};


/**
 *  This function deviates from the JavaScript 1.6 specification: it does not
 *  guarantee how many times the callback will be invoked.
 */
Array.prototype.every = function(callback, thisObject) {
  return (this.filter(callback, thisObject).length == this.length);
}


/**
 *  This function deviates from the JavaScript 1.6 specification: it does not
 *  guarantee how many times the callback will be invoked.
 */
Array.prototype.some = function(callback, thisObject) {
  return (this.filter(callback, thisObject).length > 0);
}


/**
 *  This is an object-aware mapper similar to Array.map(). The difference
 *  between the traditional methods (map, each, filter) and the pull methods
 *  (pull, pullEach, pullFilter) is that the pull methods treat the array
 *  as a list of objects and the callback as a method to apply to the objects.
 *
 *  For instance, you can Array.pull() a list of strings with the expected
 *  result:
 *
 *    ['zebra', 'pancake'].pull(''.toUpperCase);
 *
 *  Using map() would be more cumbersome and requires creation of an anonymous
 *  function:
 *
 *    ['zebra', 'pancake'].pull(function(s) { return s.toUpperCase(); });
 *
 *  While map() is ultimately more versatile, pull() can express some maps
 *  more succinctly.
 *
 *  @author epriestley
 */
Array.prototype.pull = function(callback /*, args */) {
  if (this == window) {
    throw new TypeError();
  }

  if (typeof(callback) !== "function") {
    throw new TypeError();
  }

  var args  = Array.prototype.slice.call(arguments, 1);
  var len   = this.length;
  var r     = this.alloc(len);

  for (ii = 0; ii < len; ++ii) {
    if (ii in this) {
      r[ii] = callback.apply(this[ii], args);
    }
  }

  return r;
}

Array.prototype.pullEach = function(callback /*, args */) {
  this.pull.apply(this, arguments);
  return this;
}

Array.prototype.filterEach = function(callback /*, args */) {
  var map = this.pull.apply(this, arguments);
  var len = this.length;
  var r   = this.alloc();

  for (var ii = 0; ii < len; ++ii) {
    if (ii in this) {
      r.push(this[ii]);
    }
  }

  return r;
}


//  These methods are present in some browsers and unsafe. They are not
//  generally useful; we simply remove them rather than providing safe
//  implementations.

Array.prototype.reduce      = null;
Array.prototype.reduceRight = null;


//  These methods are unsafe but highly useful; we reimplement them in terms
//  of themselves with FBJS safety.

Array.prototype.sort = (function(sort) { return function(callback) {
  return (this == window) ? null : (callback ? sort.call(this, function(a,b) {
    return callback(a,b)}) : sort.call(this));
}})(Array.prototype.sort);

Array.prototype.reverse = (function(reverse) { return function() {
  return (this == window) ? null : reverse.call(this);
}})(Array.prototype.reverse);

Array.prototype.concat = (function(concat) { return function() {
  return (this == window) ? null : concat.apply(this, arguments);
}})(Array.prototype.concat);

Array.prototype.slice = (function(slice) { return function() {
  return (this == window) ? null : slice.apply(this, arguments);
}})(Array.prototype.slice);


//  Redefine Array.clone() in terms of (safe) Array.slice().

Array.prototype.clone = Array.prototype.slice;


//  This is a Javascript 1.6 function which we implement using the native
//  version if it is available.

if (Array.prototype.indexOf) {
  Array.prototype.indexOf = (function(indexOf) {
    return function(val, index) {
      return (this == window) ? null : indexOf.apply(this, arguments);
    }
  })(Array.prototype.indexOf);
} else {
  /**
   *  This function conforms to the JavaScript 1.6 specification.
   */
  Array.prototype.indexOf = function(val, index) {
    if (this == window) {
      throw new TypeError();
    }

    var len = this.length;
    var from = Number(index) || 0;
    from = (from < 0)
         ? Math.ceil(from)
         : Math.floor(from);

    if (from < 0) {
      from += len;
    }

    for (; from < len; from++) {
      if (from in this && this[from] === val) {
        return from;
      }
    }
    return -1;
  };
}



  /**************  lib/type/object.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  Delete troublesome Object properties and provide some helper functions.
 *
 *  @author marcel
 *
 *  @provides object-extensions
 */

// Safety for FBJS.
if (Object.prototype.eval) {
  window.eval = Object.prototype.eval;
}
delete Object.prototype.eval;     // silly Mozilla
delete Object.prototype.valueOf;  // sorry, use Object.valueOf instead

function is_scalar(v) {

  switch (typeof(v)) {
    case 'string':
    case 'number':
    case 'null':
    case 'boolean':
      return true;
  }

  return false;
}

function is_empty(obj) {
  for (var i in obj) {
    return false;
  }
  return true;
}

function object_keys(obj) {
  var keys = [];
  for (var i in obj) {
    keys.push(i);
  }
  return keys;
}

function object_values(obj) {
  var values = [];
  for (var i in obj) {
    values.push(obj[i]);
  }
  return values;
}

function object_key_count(obj) {
  var count = 0;
  for (var i in obj) {
    count++;
  }
  return count;
}

function are_equal(a, b) {
  return JSON.encode(a) == JSON.encode(b);
}




  /**************  lib/type/function.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  oh gods
 *
 *  @author   marcel, epriestley
 *
 *  @provides function-extensions
 */


//
// OOP implementation
Function.prototype.extend = function(superclass) {
  var superprototype = __metaprototype(superclass, 0);
  var subprototype = __metaprototype(this, superprototype.prototype.__level + 1);
  subprototype.parent = superprototype;
}

function __metaprototype(obj, level) {
  if (obj.__metaprototype) {
    return obj.__metaprototype;
  }
  var metaprototype = new Function();

  // The "construct" function here is a little confusing...
  // metaprototype.construct goes to __metaprototype_construct which initializes the .parent objects
  // metaprototype.prototype.construct simply redirects back to the regular constructor
  // So when we call .parent.construct for the first time, the parents will be initialized but then when the next
  //   constructor calls .parent.construct it'll skip the OOP construction part
  metaprototype.construct = __metaprototype_construct;
  metaprototype.prototype.construct = __metaprototype_wrap(obj, level, true);
  metaprototype.prototype.__level = level;
  metaprototype.base = obj;
  obj.prototype.parent = metaprototype;
  obj.__metaprototype = metaprototype;
  return metaprototype;
}

function __metaprototype_construct(instance) {

  // Initialize the metaprototype... we do this on construction so that the .extend call does less work
  __metaprototype_init(instance.parent);

  // Construct a parent object for each level of inheritance
  var parents = [];
  var obj = instance;
  while (obj.parent) {
    parents.push(new_obj = new obj.parent());
    new_obj.__instance = instance;
    obj = obj.parent;
  }
  instance.parent = parents[1];
  parents.reverse();
  parents.pop();
  instance.__parents = parents;
  instance.__instance = instance;

  // Call the parent constructor
  return instance.parent.construct.apply(instance.parent, arguments);
}

function __metaprototype_init(metaprototype) {

  // Initialize the parent prototypes, and then copy\reference all their attributes to this one
  if (metaprototype.initialized) return;
  var base = metaprototype.base.prototype;
  if (metaprototype.parent) {
    __metaprototype_init(metaprototype.parent);
    var parent_prototype = metaprototype.parent.prototype;
    for (i in parent_prototype) {
      if (i != '__level' && i != 'construct' && base[i] === undefined) {
        base[i] = metaprototype.prototype[i] = parent_prototype[i]
      }
    }
  }
  metaprototype.initialized = true;

  // Wrap all the methods of this prototype with the metaprototype wrapper
  var level = metaprototype.prototype.__level;
  for (i in base) {
    if (i != 'parent') {
      base[i] = metaprototype.prototype[i] = __metaprototype_wrap(base[i], level);
    }
  }
}

function __metaprototype_wrap(method, level, shift) {
  if (typeof method != 'function' || method.__prototyped) {
    return method;
  }
  var func = function() {
    var instance = this.__instance;
    if (instance) {
      var old_parent = instance.parent;
      instance.parent = level ? instance.__parents[level - 1] : null;
      if (shift) {
        var args = [];
        for (var i = 1; i < arguments.length; i++) {
          args.push(arguments[i]);
        }
        var ret = method.apply(instance, args);
      } else {
        var ret = method.apply(instance, arguments);
      }
      instance.parent = old_parent;
      return ret;
    } else {
      return method.apply(this, arguments);
    }
  }
  func.__prototyped = true;
  return func;
}

/**
 *  Fancy new version of Function.bind which can curry. See bind() for a
 *  slightly more comprehensive description.
 *
 *  @author epriestley
 */
Function.prototype.bind = function(context /*, arg, arg, arg*/) {
  var argv = [ arguments[0], this ];
  var argc = arguments.length;
  for (var ii = 1; ii < argc; ii++) {
    argv.push(arguments[ii]);
  }

  return bind.apply( null, argv );
}

/**
 * Run the function at the end of this event loop, i.e. after
 * a timeout of zero milliseconds.
 */
Function.prototype.defer = function() {
  setTimeout(this, 0);
}

/**
 *  This function accepts and discards inputs; it has no side effects. This is
 *  primarily useful idiomatically for overridable function endpoints which
 *  always need to be callable, since JS lacks a null-call idiom ala Cocoa.
 *
 *  @author epriestley
 */
function bagofholding() {
  return undefined;
}

/**
 *  This function accepts and returns one input.  This is useful for functions
 *  like html_wordwrap() which accept closures to do further processing.  Pass
 *  'id' as the closure, and the processing will be a no-op.
 *
 *  @author jwiseman
 */
function identity(input) {
  return input;
}

/**
 * Executes a handler that has been specified as either a function or as
 * a string of JavaScript code.
 *
 * @param obj       The `this`-argument to be passed to the function.
 * @param func      Either a function, or some JavaScript code to evaluated.
 * @param args_map  An object that maps the names of arguments to their values.
 *                  If `func` is a string, it can then make mention of those
 *                  arguments by name.
 * @return          Whatever is returned by the function.
 *
 * @author jrosenstein
 */
function call_or_eval(obj, func, args_map /* = {} */) {
  if (!func) {
    return undefined;
  }
  args_map = args_map || {};

  if (typeof(func) == 'string') {
    var params = object_keys(args_map).join(', ');
    // The .f weirdness here is to satisfy IE6, which evals functions as
    // undefined, but handles objects containing functions just fine.
    func = eval('({f: function(' + params + ') { ' + func + '}})').f;
  }
  if (typeof(func) != 'function') {
    Util.error('handler was neither a function nor a string of JS code');
    return undefined;
  }

  return func.apply(obj, object_values(args_map));
}




  /**************  lib/type/string.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides string-extensions
 */

String.prototype.trim = function() {
  if (this == window) {
    return null;
  }
  return this.replace(/^\s*|\s*$/g, '');
}

function trim(text) {
  return String(text).trim();
}

String.prototype.startsWith = function(substr) {
  if (this == window) {
    return null;
  }
  return this.substring(0, substr.length) == substr;
};

//----------------------------------------------------------------------------------------------
/* Cross-Browser Split v0.1; MIT-style license
By Steven Levithan <http://stevenlevithan.com>
An ECMA-compliant, uniform cross-browser split method */
/* several modifications by marcel for performance. he loves MIT licenses. */
String.prototype.split = (function(split) {
  return function(separator, limit) {
  var flags = "";

  /* Behavior for separator: If it's...
  - Undefined: Return an array containing one element consisting of the entire string
  - A regexp or string: Use it
  - Anything else: Convert it to a string, then use it */
  if (separator === null || limit === null) {
    return [];
  } else if (typeof separator == 'string') {
    return split.call(this, separator, limit);
  } else if (separator === undefined) {
    return [this.toString()]; // toString is used because the typeof this is object
  } else if (separator instanceof RegExp) {

    if (!separator._2 || !separator._1) {
      flags = separator.toString().replace(/^[\S\s]+\//, "");
      if (!separator._1) {
        if (!separator.global) {
          separator._1 = new RegExp(separator.source, "g" + flags);
        } else {
          separator._1 = 1;
        }
      }
    }
    separator1 = separator._1 == 1 ? separator : separator._1;

    // Used for the IE non-participating capturing group fix
    var separator2 = (separator._2 ? separator._2 : separator._2 = new RegExp("^" + separator1.source + "$", flags));

    /* Behavior for limit: If it's...
    - Undefined: No limit
    - Zero: Return an empty array
    - A positive number: Use limit after dropping any decimal value (if it's then zero, return an empty array)
    - A negative number: No limit, same as if limit is undefined
    - A type/value which can be converted to a number: Convert, then use the above rules
    - A type/value which cannot be converted to a number: Return an empty array */
    if (limit === undefined || limit < 0) {
      limit = false;
    } else {
      limit = Math.floor(limit);
      if (!limit) return []; // NaN and 0 (the values which will trigger the condition here) are both falsy
    }

    var match,
    output = [],
    lastLastIndex = 0,
    i = 0;

    while ((limit ? i++ <= limit : true) && (match = separator1.exec(this))) {
      // Fix IE's infinite-loop-resistant but incorrect RegExp.lastIndex
      if ((match[0].length === 0) && (separator1.lastIndex > match.index)) {
        separator1.lastIndex--;
      }

      if (separator1.lastIndex > lastLastIndex) {
        /* Fix IE to return undefined for non-participating capturing groups (NPCGs). Although IE
        incorrectly uses empty strings for NPCGs with the exec method, it uses undefined for NPCGs
        with the replace method. Conversely, Firefox incorrectly uses empty strings for NPCGs with
        the replace and split methods, but uses undefined with the exec method. Crazy! */
        if (match.length > 1) {
          match[0].replace(separator2, function() {
            for (var j = 1; j < arguments.length - 2; j++) {
              if (arguments[j] === undefined) match[j] = undefined;
            }
          });
        }

        output = output.concat(this.substring(lastLastIndex, match.index), (match.index === this.length ? [] : match.slice(1)));
        lastLastIndex = separator1.lastIndex;
      }

      if (match[0].length === 0) {
        separator1.lastIndex++;
      }
    }

    return (lastLastIndex === this.length)
         ? (separator1.test("") ? output : output.concat(""))
         : (limit ? output : output.concat(this.substring(lastLastIndex)));
  } else {
    return split.call(this, separator, limit); // this should probably never happen...
  }
}})(String.prototype.split);





  /**************  lib/type/list.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  This is a generic "Array-like" object which implements some of Array's
 *  behavior. We need to do this because IE will fatally break anything which
 *  extends Array; Dean Edwards has a more in-depth explanation:
 *
 *    http://dean.edwards.name/weblog/2006/11/hooray/
 *
 *  We avoid the iframe magic because the cost of building our own Array-like
 *  object is not high; we lose some Array behaviors like being able to assign
 *  to an arbitrary index and get an array that long, but these are a small
 *  price to pay. This is similar to jQuery's approach.
 *
 *  Basically, we need to keep track of length ourselves and can mostly fall
 *  back to Array to do anything even mildly interesting.
 *
 *  @author   epriestley
 *
 *  @requires array-extensions
 *  @provides list
 */

function /* class */ List(length) {
  if (arguments.length > 1) {
    for (var ii = 0; ii < arguments.length; ii++) {
      this.push(arguments[ii]);
    }
  } else {
    this.resize(length || 0);
  }
}

List.prototype.length = 0;
List.prototype.size = function() {
  return this.length;
}

List.prototype.resize = function(new_size) {
  this.length = new_size;
  return this;
}

List.prototype.push = function(element) {
  this.length += arguments.length;
  return Array.prototype.push.apply(this, arguments);
}

List.prototype.pop = function() {
  --this.length;
  return Array.prototype.pop.apply(this);
}

List.prototype.alloc = function(n) {
  return new List(n);
}

//  Pull in all the Array behaviors we're interested in.

List.prototype.map        = Array.prototype.map;
List.prototype.forEach    = Array.prototype.forEach;
List.prototype.each       = Array.prototype.each;
List.prototype.filter     = Array.prototype.filter;
List.prototype.every      = Array.prototype.every;
List.prototype.some       = Array.prototype.some;
List.prototype.pull       = Array.prototype.pull;
List.prototype.pullEach   = Array.prototype.pullEach;
List.prototype.pullFilter = Array.prototype.pullFilter;


  /**************  lib/ua/ua.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides ua
 */

/**
 *  User Agent and OS detection. Usage is straightforward:
 *
 *    if (ua.ie( )) {
 *      //  IE
 *    }
 *
 *  You can also do version checks:
 *
 *    if (ua.ie( ) >= 7) {
 *      //  IE7 or better
 *    }
 *
 *  The browser functions will return NaN if the browser does not match, so
 *  you can also do version compares the other way:
 *
 *    if (ua.ie( ) < 7) {
 *      //  IE6 or worse
 *    }
 *
 *  Note that the version is a float and may include a minor version number,
 *  so you should always use range operators to perform comparisons, not
 *  strict equality.
 *
 *  NOTE: You should also STRONGLY prefer capability detection to browser
 *  version detection where it's reasonable:
 *  http://www.quirksmode.org/js/support.html
 *
 *  Further, we have a large number of mature wrapper functions and classes
 *  which abstract away many browser irregularities. Check the docs or grep
 *  things before writing yet another copy of "event || window.event".
 *
 *  @task browser   Determining the User Agent
 *  @task os        Determining the User's Operating System
 *  @task internal  Internal methods
 *
 *  @author marcel, epriestley
 */
var ua = {

  /**
   *  Check if the UA is Internet Explorer.
   *
   *  @task browser
   *  @access public
   *
   *  @return float|NaN Version number (if match) or NaN.
   *  @author marcel
   */
  ie: function() {
    return this._ie;
  },


  /**
   *  Check if the UA is Firefox.
   *
   *  @task browser
   *  @access public
   *
   *  @return float|NaN Version number (if match) or NaN.
   *  @author marcel
   */
  firefox: function() {
    return this._firefox;
  },


  /**
   *  Check if the UA is Opera.
   *
   *  @task browser
   *  @access public
   *
   *  @return float|NaN Version number (if match) or NaN.
   *  @author marcel
   */
  opera: function() {
    return this._opera;
  },


  /**
   *  Check if the UA is Safari.
   *
   *  @task browser
   *  @access public
   *
   *  @return float|NaN Version number (if match) or NaN.
   *  @author marcel
   */
  safari: function() {
    return this._safari;
  },


  /**
   *  Check if the user is running Windows.
   *
   *  @task os
   *  @return bool `true' if the user's OS is Windows.
   *  @author marcel
   */
  windows: function() {
    return this._windows;
  },


  /**
   *  Check if the user is running Mac OS X.
   *
   *  @task os
   *  @return bool `true' if the user's OS is Mac OS X.
   *  @author marcel
   */
  osx: function() {
    return this._osx;
  },


  /**
   *  Populate the UA and OS information.
   *
   *  @access public
   *  @task internal
   *
   *  @return void
   *
   *  @author marcel
   */
  populate : function() {

    var agent = /(?:MSIE.(\d+\.\d+))|(?:(?:Firefox|GranParadiso|Iceweasel).(\d+\.\d+))|(?:Opera.(\d+\.\d+))|(?:AppleWebKit.(\d+(?:\.\d+)?))/.exec(navigator.userAgent);
    var os    = /(Mac OS X;)|(Windows;)/.exec(navigator.userAgent);

    if (agent) {
      ua._ie      = agent[1] ? parseFloat(agent[1]) : NaN;
      ua._firefox = agent[2] ? parseFloat(agent[2]) : NaN;
      ua._opera   = agent[3] ? parseFloat(agent[3]) : NaN;
      ua._safari  = agent[4] ? parseFloat(agent[4]) : NaN;
    } else {
      ua._ie      =
      ua._firefox =
      ua._opera   =
      ua._safari  = NaN;
    }

    if (os) {
      ua._osx     = !!os[1];
      ua._windows = !!os[2];
    } else {
      ua._osx     =
      ua._windows = false;
    }
  }
};



  /**************  lib/event/extensions.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *
 *  @provides event-extensions
 */

/**
 *  Chain two or more event handlers together, returning a function that calls
 *  them in sequence. Note that these functions are treated like event
 *  functions: if one of them returns a strict `false', execution will abort and
 *  subsequent functions WILL NOT be called.
 *
 *  The common use case is making sure you don't overwrite existing event
 *  handlers:
 *
 *  <js>
 *    button.onclick = chain(button.onclick, additionalHandler);
 *  </js>
 *
 *  It is safe to pass `null' values to chain, so it's probably not a bad idea
 *  to use this idiom generally when performing event assignments.
 *
 *  @params Zero or more functions to chain together
 *
 *  @return A function which executes the arguments in order, aborting if any
 *          return a strict `false'. This function will return `false' to
 *          indicate that some component function aborted event bubbling, or
 *          `true' to indicate that all functions executed.
 *
 *  @author epriestley
 */
function chain( u, v /*, w, x ... */ ) {

  var calls = [];
  for (var ii = 0; ii < arguments.length; ii++) {
    calls.push(arguments[ii]);
  }

  return function( ) {
    for (var ii = 0; ii < calls.length; ii++) {
      if ( calls[ii] && calls[ii].apply( this, arguments ) === false ) {
        return false;
      }
    }
    return true;
  }

}


// === Event Attaching ===
// (see: http://www.quirksmode.org/blog/archives/2005/10/_and_the_winner_1.html)

// why name_hash? So you can use the same function and pass different name_hashes and ie won't get confused
function addEventBase(obj, type, fn, name_hash)
{
  if (obj.addEventListener) {
    obj.addEventListener( type, fn, false );
  }
  else if (obj.attachEvent)
  {
    var fn_name = type+fn+name_hash;
    obj["e"+fn_name] = fn;
    obj[fn_name] = function() { obj["e"+fn_name]( window.event ); }
    obj.attachEvent( "on"+type, obj[fn_name] );
  }

  return fn;

}

function removeEventBase(obj, type, fn, name_hash)
{
  if (obj.removeEventListener) {
    obj.removeEventListener( type, fn, false );
  }
  else if (obj.detachEvent)
  {
    var fn_name = type+fn+name_hash;
    if (obj[fn_name]) {
      obj.detachEvent( "on"+type, obj[fn_name]);
      obj[fn_name] = null;
      obj["e"+fn_name] = null;
    }
  }
}



// for IE
function event_get(e) {
  return e || window.event;
}

/**
 *  @browser Safari, Firefox
 *    Event target is in `target'.
 *
 *  @browser IE
 *    Event target is in `srcElement'.
 */
function event_get_target(e) {
  return (e = event_get(e)) && (e['target'] || e['srcElement']);
}

function event_abort(e) {
  (e = event_get(e)) && (e.cancelBubble = true) &&
    e.stopPropagation && e.stopPropagation();
  return false;
}

function event_prevent(e) {
  (e = event_get(e)) && !(e.returnValue = false) &&
    e.preventDefault && e.preventDefault();
  return false;
}

function event_kill(e) {
  return event_abort(e) || event_prevent(e);
}

function event_get_keypress_keycode(event) {
  event = event_get(event);
  if (!event) {
    return false;
  }
  switch (event.keyCode) {
    case 63232: // up
      return 38;
    case 63233: // down
      return 40;
    case 63234: // left
      return 37;
    case 63235: // right
      return 39;
    case 63272: // delete
    case 63273: // home
    case 63275: // end
      return null; // IE doesn't support these so they shouldn't be used
    case 63276: // page up
      return 33;
    case 63277: // page down
      return 34;
  }
  if (event.shiftKey) {
    switch (event.keyCode) {
      case 33: // page up
      case 34: // page down
      case 37: // left
      case 38: // up
      case 39: // right
      case 40: // down
        return null; // "!" (and others) can not be detected with this abstraction,
                     // but there will never be a false position on arrow keys
    }
  } else {
    return event.keyCode;
  }
}

function stopPropagation(e) {
    if (!e) var e = window.event;
    e.cancelBubble = true;
    if (e.stopPropagation) {
        e.stopPropagation();
    }
}




  /**************  lib/event/onload.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @requires event-extensions util ua
 *  @provides onload
 */

/**
 *  Register a function for execution before a page is loaded. If the page
 *  loads, functions registered in this way are guaranteed to: execute; execute
 *  exactly once; execute in the order they are registered; and execute after
 *  the DOM is ready.
 *
 *  Note, however, that execution will be attempted in response to the
 *  DOMContentLoaded event, and will succeed to some degree in at least Firefox
 *  2, Safari 2, IE6, and IE7. This means that your onload handler may (and
 *  probably will) fire BEFORE images are loaded or the page is flushed to the
 *  display -- this is generally good, because it prevents a flash of content
 *  before onload handlers fire. However, it also means that you MUST NOT
 *  perform operations which depend on image dimensions, because they probably
 *  will not be available or correct.
 *
 *  A primitive, dependentless version of this function is rendered during
 *  start_html, so it should pretty much be safe to queue up handlers from
 *  anywhere using onloadRegister.
 *
 *  @author marcel, epriestley
 */
window.onloadRegister = function(handler) {
  // If implementation changes, make sure to update primitive version
  // rendered in start_html.
  window.loaded ? _runHook(handler) : _addHook('onloadhooks', handler);
};

/**
 *  Register a function for execution after a page is loaded. These functions
 *  are guaranteed to execute after the window.onload event and after any hooks
 *  registered by onloadRegister().
 */
function onafterloadRegister(handler) {
  window.loaded ? _runHook(handler) : _addHook('onafterloadhooks', handler);
}


/**
 * If you omit the include_quickling_events argument from onunloadRegister or
 * onbeforeunloadRegister, then those will default to respect Quickling
 * navigation iff either:
 *
 *   - you're making the call some time between start_page and close_page, or
 *   - you're making the call after the page is done loading.
 *
 * The effect we're going for is that we respect Quickling events when the
 * call is made 'by the content of the page', but don't if it was requested
 * 'by the chrome of page' (e.g. Chirp).
 */
function _include_quickling_events_default() {
  return window.loading_initial_content_div || window.loaded;
}


/**
 *  Register a function for execution in response to the window's onbeforeunload
 *  event. Because these functions may be executed an arbitrary number of times,
 *  this event is probably not generally useful except for warning users that
 *  they have unsaved changes; instead, use onunloadRegister(). Functions
 *  executing here must not behave like normal event functions -- instead, they
 *  should return a string to prompt the browser to generate a warning dialog.
 *
 *  If `onbeforeunload' returns a string, browsers will prompt the user with
 *  a dialog which includes the string and asks the user to confirm that they
 *  want to navigate away from the page.
 *
 *  These are the strings reported by browsers, so this will turn up when
 *  the code is grepped for; we had some trouble debugging this because no
 *  one knew this mechanism existed and these strings aren't greppable since
 *  they're in the browser:
 *
 *    Are you sure you want to navigate away from this page?
 *
 *    [The return value string.]
 *
 *    Press OK to continue, or Cancel to stay on the current page.
 *
 *  @param include_quickling_events  (optional -- see _include_quickling_events_default for default behavior)
 *                                   Run the handler the next time the user
 *                                   leaves the page OR navigates somewhere
 *                                   using full-page Quickling.
 *
 *  @author epriestley, jrosenstein
 */
function onbeforeunloadRegister(handler, include_quickling_events /* optional */) {
  if (include_quickling_events === undefined) {
    include_quickling_events = _include_quickling_events_default();
  }

  if (include_quickling_events) {
    _addHook('onbeforeleavehooks', handler);
  } else {
    _addHook('onbeforeunloadhooks', handler);
  }
}


/**
 *  Register a function for execution before the page is unloaded. Functions
 *  registered in this way are guaranteed to execute; guaranteed to execute
 *  exactly once; guaranteed to execute in the order they are registered; and
 *  guaranteed to execute in response to the window's onbeforeunload event.
 *
 *  @param include_quickling_events  (optional -- see _include_quickling_events_default for default behavior)
 *                                   Run the handler the next time the user
 *                                   leaves the page OR navigates somewhere
 *                                   using full-page Quickling.
 *
 *  @author epriestley, jrosenstein
 */
function onunloadRegister(handler, include_quickling_events /* optional */) {
  if (include_quickling_events === undefined) {
    include_quickling_events = _include_quickling_events_default();
  }

  if (include_quickling_events) {
    _addHook('onleavehooks', handler);
  } else {
    _addHook('onunloadhooks', handler);
  }
}


/**
 *  Hook function called "onload" -- this probably means DOMContentReady, not
 *  window.onload. Use onloadRegister() to register functions for onload
 *  execution; see that function for more information about how "onload"
 *  handlers work and when they will be executed.
 *
 *  @author marcel, epriestley
 */
function _onloadHook() {
  window.loading_begun = true;
  !window.loaded && window.Env &&
    (Env.t_willonloadhooks=(new Date()).getTime());
  _runHooks('onloadhooks');
  !window.loaded && window.Env &&
    (Env.t_doneonloadhooks=(new Date()).getTime());
  window.loaded = true;
}

function _runHook(handler) {
  try {
    handler( );
  } catch (ex) {
    Util.error('Uncaught exception in hook (run after page load): %x', ex);
  }
}

function _runHooks(hooks) {

  var isbeforeunload = hooks == 'onbeforeleavehooks'
                    || hooks == 'onbeforeunloadhooks';
  var warn = null;

  do {

    var h = window[hooks];
    if (!isbeforeunload) {
      window[hooks] = null;
    }

    if (!h) {
      break;
    }

    for (var ii = 0; ii < h.length; ii++) {
      try {
        if (isbeforeunload) {
          warn = warn || h[ii]();
        } else {
          h[ii]();
        }
      } catch (ex) {
        Util.error('Uncaught exception in hook (%q) #%d: %x', hooks, ii, ex);
      }
    }

    if (isbeforeunload) {
      break;
    }

  } while (window[hooks]);

  if (isbeforeunload && warn) {
    return warn;
  }
}

function _addHook(hooks, handler) {
  (window[hooks] ? window[hooks] : (window[hooks] = [])).push(handler);
}

/**
 *  Bootstrap hooks for `onload', `onbeforeunload', and `onunload' handlers. Use
 *  the functions onloadRegister(), onbeforeunloadRegister(), and
 *  onunloadRegister() to register events for execution; see those functions
 *  for details on what they do, what guarantees they provide, and when they
 *  will fire their handlers.
 *
 *  @author marcel, epriestley
 */
function _bootstrapEventHandlers( ) {

  if (document.addEventListener) {
    if (ua.safari()) {
      var timeout = setInterval(function() {
        if (/loaded|complete/.test(document.readyState)) {
          (window.Env&&(Env.t_domcontent=(new Date()).getTime()));
          _onloadHook();
          clearTimeout(timeout);
        }
      }, 3);
    } else {
      document.addEventListener("DOMContentLoaded", function() {
        (window.Env&&(Env.t_domcontent=(new Date()).getTime()));
        _onloadHook();
        }, true);
    }
  } else {

    var src = 'javascript:void(0)';
    if (window.location.protocol == 'https:') {
      //  The `Gomez' monitoring software freaks out about this a bit, but
      //  browser behavior seems correct.
      src = '//:';
    }

    //  If a client tries to render base.js inline, many browsers will identify
    //  the closing script tag in the string below as the actual end of the
    //  inline script. Escaping the / and > prevents this from happening without
    //  changing the semantics.
    document.write(
      '<script onreadystatechange="if (this.readyState==\'complete\') {'       +
      '(window.Env&&(Env.t_domcontent=(new Date()).getTime()));'               +
      'this.parentNode.removeChild(this);_onloadHook();}" defer="defer" '      +
      'src="' + src + '"><\/script\>');
  }

  //  We need to chain here because Cavalry writes directly to window.onload
  //  and currently needs to register itself before any Javascript includes
  //  get pulled in. With the advent of Env.start, this is technically
  //  unnecessary, but it's not hurting anything for now.
  window.onload = chain(
    window.onload,
    function() {


      //  Force layout before firing onload; this affects Safari 3 and gives us
      //  better rendering benchmarks and more consistent behavior; it can
      //  degrade performance but pretty much anything you're doing should be
      //  onloadRegistered() anyway, which will fire and take effect before
      //  we force a layout.

      //    http://www.howtocreate.co.uk/safaribenchmarks.html


      (window.Env&&(Env.t_layout=(new Date()).getTime()));
      var force_layout = document && document.body && document.body.offsetWidth;
      (window.Env&&(Env.t_onload=(new Date()).getTime()));


      _onloadHook( );
      _runHooks('onafterloadhooks');
    });

  window.onbeforeunload = function( ) {
    var warn = _runHooks('onbeforeleavehooks')
            || _runHooks('onbeforeunloadhooks');
    if (!warn) {
      window.loaded = false;
    }
    return warn;
  };

  window.onunload = chain(
    window.onunload,
    function( ) {
      _runHooks('onleavehooks');
      _runHooks('onunloadhooks');
    });

}

/**
 *  If Javascript is triggered in the href attribute of an anchor tag, IE will
 *  trigger an onbeforeunload event after which we will set window.loaded to
 *  false. Anything that checks this value, such as onloadRegister and
 *  subsequently dialogpro, will not function properly because they will act as
 *  if the page has not yet loaded, but an onload event will never come
 *  leaving the page in a broken state.
 *
 *  In case it is necessary to do this (which is the case when calling
 *  Javascript from Flash), then you can put this function before your function
 *  calls to fix the state of the window. However, you should use the onclick
 *  attribute instead in almost all situations.
 *
 *  BAD:
 *    <a href="javascript:some_function()">Go!</a>
 *
 *  LESS BAD:
 *    <a href="javascript:keep_window_set_as_loaded(); some_function()">Go!</a>
 *
 *  BETTER:
 *    <a href="#" onclick="some_function_that_returns_false()">Go!</a>
 *
 *  Fixing the state of the page involves setting window.loaded back to true
 *  and making sure that any onload or onafterload hooks that may have been
 *  queued get called. If window.loaded was not set to false, then nothing
 *  should happen.
 *
 *  Note: The onbeforeunload event occurs before the Javascript in the href
 *  attribute is called, so it is not prevented. Events that are registered
 *  with onbeforeunloadRegister will still be called. This just fixes the
 *  broken state of window.loaded.
 *
 *  @author blair
 */
function keep_window_set_as_loaded() {
  if (window.loaded == false) {
    window.loaded = true;
    _runHooks('onloadhooks');
    _runHooks('onafterloadhooks');
  }
}



  /**************  lib/event/controller.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

function /* class */ EventController(eventResponderObject) {

  copy_properties(this, {
        queue : [],
        ready : false,
    responder : eventResponderObject
  });

};

copy_properties(EventController.prototype, {

  startQueue : function( ) {
    this.ready = true;
    this.dispatchEvents( );
    return this;
  },

  pauseQueue : function( ) {
    this.ready = false;
    return this;
  },

  addEvent : function(event) {

    if (event.toLowerCase() !== event) {
      Util.warn(
        'Event name %q contains uppercase letters; events should be lowercase.',
        event);
    }

    var args = [];
    for (var ii = 1; ii < arguments.length; ii++) {
      args.push(arguments[ii]);
    }

    this.queue.push({ type: event, args: args });
    if (this.ready) {
      this.dispatchEvents( );
    }

    return false;
  },

  dispatchEvents : function( ) {

    if (!this.responder) {
      Util.error(
        'Event controller attempting to dispatch events with no responder! '   +
        'Provide a responder when constructing the controller.');
    }

    for (var ii = 0; ii < this.queue.length; ii++) {
      var evtName = 'on' + this.queue[ii].type;
      if (typeof(this.responder[evtName]) != 'function' &&
          typeof(this.responder[evtName]) != 'null') {
        Util.warn(
          'Event responder is unable to respond to %q event! Implement a %q '  +
          'method. Note that method names are case sensitive; use lower case ' +
          'when defining events and event handlers.',
          this.queue[ii].type,
          evtName);
      } else {
        if (this.responder[evtName]) {
          this.responder[evtName].apply(this.responder, this.queue[ii].args);
        }
      }
    }
    this.queue = [];
  }

});




  /**************  lib/ua/adjust.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @requires ua onload
 *  @provides ua-adjust
 */

/**
 *  Function for UA-specific global behavior adjustments. This is basically
 *  the very definition of a giant pile of hacks. This is automatically called
 *  in base.js.
 *
 *  @access public
 *  @task   internal
 *
 *  @return void
 *
 *  @author epriestley
 */
function adjustUABehaviors( ) {
  onloadRegister(addSafariLabelSupport);

  //  This fixes an IE6 behavior where it doesn't cache background images.
  //  However, the fix breaks certain flavors of IE6 -- apparently anything
  //  without SP1, which includes some standalone versions? The forensics
  //  on this problem are a bit incomplete, but we were doing this in a
  //  CSS expression before so this is at least one degree less bad. See:
  //
  //    http://evil.che.lu/2006/9/25/no-more-ie6-background-flicker
  //    http://misterpixel.blogspot.com/2006/09/note-on-
  //      backgroundimagecache-command.html

  if (ua.ie() < 7) {
    try {
      document.execCommand('BackgroundImageCache', false, true);
    } catch (ignored) {
      //  Ignore, we're in some IE6 without SP1 and it didn't take.
    }
  }
}


/**
 *  Safari 2 doesn't have complete label support, but this fixes that.
 *
 *  @author rgrover
 */
function addSafariLabelSupport(base) {
  if (ua.safari() < 500) {
    var labels = (base || document.body).getElementsByTagName("label");
    for (i = 0; i < labels.length; i++) {
      labels[i].addEventListener('click', addLabelAction, true);
    }
  }
}

/**
 *  Support function for addSafariLabelSupport
 *  This is what gets called when clicking a label
 *  to make sure the right radio/checkbox gets chosen.
 *
 *  @author rgrover
 */
function addLabelAction(event) {
  var id = this.getAttribute('for');
  var item = null;
  if (id) {
    item = document.getElementById(id);
  } else {
    item = this.getElementsByTagName('input')[0];
  }
  if (!item || event.srcElement == item) {
    return;
  }
  if (item.type == 'checkbox') {
    item.checked = !item.checked;
  } else if (item.type == 'radio') {
    var radios = document.getElementsByTagName('input');
    for (i = 0; i < radios.length; i++) {
      if (radios[i].name == item.name && radios[i].form == item.form) {
        radios.checked = false;
      }
    }
    item.checked = true;
  } else {
    // sometimes focusing a checkbox has a weird side-effect (like on the
    // condensed multi-friend-selector)
    item.focus();
  }
  if (item.onclick) {
    item.onclick(event); // make sure events attached to this guy get triggered
  }
}



  /**************  lib/string/escape.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides string-escape
 */

function escapeURI(u)
{
    if (encodeURIComponent) {
        return encodeURIComponent(u);
    }
    if (escape) {
        return escape(u);
    }
}



/**
 *  Escape HTML characters in a string, rendering it safe for display in an
 *  HTML context.
 *
 *  @param string String to escape.
 *  @return string Escaped string.
 *
 *  @author marcel
 */
function htmlspecialchars(text) {

  if (typeof(text) == 'undefined' || !text.toString) {
    return '';
  }

  if (text === false) {
    return '0';
  } else if (text === true) {
    return '1';
  }

  return text
    .toString()
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function htmlize(text) {
 return htmlspecialchars(text).replace(/\n/g, '<br />');
}


/**
 *  Escape quote charcters in a string, rendering it safe for use as a parameter
 *  to a literally constructed function (e.g. an onclick handler in a link being
 *  created via innerHTML).
 *
 *  @param string String to escape.
 *  @return string Escaped string.
 *
 *  @author marcel
 */
function escape_js_quotes(text) {

  if (typeof(text) == 'undefined' || !text.toString) {
    return '';
  }

  return text
    .toString( )
    .replace(/\\/g, '\\\\')
    .replace(/\n/g, '\\n')
    .replace(/\r/g, '\\r')
    .replace(/"/g, '\\x22')
    .replace(/'/g, '\\\'')
    .replace(/</g, '\\x3c')
    .replace(/>/g, '\\x3e')
    .replace(/&/g, '\\x26');
}


  /**************  lib/string/misc.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides string-misc
 *  @author jwiseman
 */

/**
 * Given raw text, inserts word breaks ("<wbr/>") into continuous strings of
 * characters longer than wrap_limit.  This allows the text to be displayed
 * in a constrained area without overflowing.  The wrap_limit is character-
 * based, so unless you're using a fixed-width font, you'll have to be
 * conservative to make sure a string like "WWWWWWWW..." isn't too wide.
 * You may pass an optional processing function for the text.  It defaults to
 * htmlize, as you'll usually want to get rid of special HTML characters
 * (except any "<wbr/>" we added).  If for some reason you don't want to
 * htmlize, call the function as:
 *
 * var str_wrapped = html_wordwrap(str_raw, 30, id);
 *
 * If you need a version of wordwrap that takes htmlized-text, feel free to
 * write it.  You'll have to take care to treat "&gt;", etc. as a single
 * character, or else you might insert a "<wbr/>" right in the middle of it.
 *
 * @param  string   str         The string to word wrap
 * @param  int      wrap_limit  Defaults to 60
 * @param  function txt_fn      Optional processing function, defaults to htmlize
 * @return string               Wrapped string
 * @author jwiseman
 */
function html_wordwrap(str, wrap_limit, txt_fn) {
  if (typeof wrap_limit == 'undefined') {
    wrap_limit = 60;
  }
  if (typeof txt_fn != 'function') {
    txt_fn = htmlize;
  }

  // match continuous ranges of non-whitespace characters.
  var regex = new RegExp("\\S{"+(wrap_limit+1)+"}", 'g');

  var start = 0;
  var str_remaining = str;

  // build the return value as an array, then join.  it's faster than lots of
  // string concats.
  var ret_arr = [];

  var matches = str.match(regex);

  if (matches) {
    for (var i = 0; i < matches.length; i++) {
      var match = matches[i];
      var match_index = start + str_remaining.indexOf(match);

      // initial chunk
      var chunk = str.substring(start, match_index);
      if (chunk) {
        ret_arr.push(txt_fn(chunk));
      }

      // long chunk
      ret_arr.push(txt_fn(match) + '<wbr/>');

      // the rest
      start = match_index + match.length;
      str_remaining = str.substring(start);
    }
  }

  // add the rest
  if (str_remaining) {
    ret_arr.push(txt_fn(str_remaining));
  }

  return ret_arr.join('');
}

/**
 * Finds the URLs in a string.
 *
 * @param  string   str  The string to search
 * @return array         The URLs
 * @author jwiseman
 */
function text_get_hyperlinks(str) {
  if (typeof(str) != 'string') {
    return [];
  }
  return str.match(/(?:(?:ht|f)tps?):\/\/[^\s<]*[^\s<\.)]/ig);
}

/**
 * Given raw text, finds all URLs (based on text_get_hyperlinks) and replaces
 * them with anchor tags hyperlinked to the URLs.
 * You may optionally pass functions which process the text as it is parsed
 * by this function.
 * For example, if the resulting text is going to be inserted into the DOM,
 * you'll want to make sure it doesn't contain unintended special characters
 * (other than the anchor tags introduced by this function).  To do this,
 * call the function as:
 *
 * var str_for_display = html_hyperlink(str_raw, htmlize, htmlize);
 *
 * Note that this is the default behavior if you don't specify the processing
 * functions, as you'll almost always want to use htmlize.
 *
 * If you want to htmlize and word wrap the text for display in a small area,
 * call the function as:
 *
 * var process_fn = function(str) {
 *   return html_wordwrap(str, 20, htmlize);
 * };
 * var str_for_display = html_hyperlink(str_raw, process_fn, process_fn);
 *
 * If for some reason you don't want the text htmlized, you can call the
 * function as:
 *
 * var str_for_display = html_hyperlink(str_raw, id, id);
 *
 * @param  string   str     String to process
 * @param  function txt_fn  Optional function for processing chunks of text,
 *                          defaults to htmlize.
 * @param  function url_fn  Optional function for processing the url text
 *                          written inside the new anchor tags, defaults to
 *                          htmlize.
 * @return string           Hyperlinked and processed by the given functions
 * @author jwiseman
 */
function html_hyperlink(str, txt_fn, url_fn) {
  var accepted_delims = {'<':'>', '*':'*', '{':'}', '[':']', "'":"'", '"':'"',
                         '#':'#', '+':'+', '-':'-', '(':')'};

  if (typeof(str) == 'undefined' || !str.toString) {
    return '';
  }
  if (typeof txt_fn != 'function') {
    txt_fn = htmlize;
  }
  if (typeof url_fn != 'function') {
    url_fn = htmlize;
  }

  var str = str.toString();
  var http_matches = text_get_hyperlinks(str);

  var start = 0;
  var str_remaining = str;

  // build the return value as an array, then join.  it's faster than lots of
  // string concats.
  var ret_arr = [];

  var str_remaining = str;

  if (http_matches) {
    for (var i = 0; i < http_matches.length; i++) {
      var http_url = http_matches[i];
      var http_index = start + str_remaining.indexOf(http_url);
      var str_len = http_url.length;

      // NON URL PART
      var non_url = str.substring(start, http_index);
      if (non_url) {
        ret_arr.push(txt_fn(non_url));
      }

      // If the URL string has a delimeter char before it, and its
      // corresponding end char is in the URL, then the URL is actually
      // what's between these two chars.
      var trailing = '';
      if (http_index > 0) {
        var delim = str[http_index-1];
        if (typeof accepted_delims[delim] != 'undefined') {
          var end_delim = accepted_delims[delim];
          var end_delim_index = http_url.indexOf(end_delim);
          if (end_delim_index != -1) {
            trailing = txt_fn(http_url.substring(end_delim_index));
            http_url = http_url.substring(0, end_delim_index);
          }
        }
      }

      // URL PART
      http_str = url_fn(http_url);
      http_url_quote_escape = http_url.replace(/"/g, '%22');
      ret_arr.push('<a href="'+http_url_quote_escape+'" target="_blank" rel="nofollow">'+
                     http_str+
                   '</a>'+trailing);

      start = http_index + str_len;
      str_remaining = str.substring(start);
    }
  }

  // Leftover tail string
  if (str_remaining) {
    ret_arr.push(txt_fn(str_remaining));
  }

  return ret_arr.join('');
}

function nl2br(text) {

  if (typeof(text) == 'undefined' || !text.toString) {
    return '';
  }

  return text
    .toString( )
    .replace( /\n/g, '<br />' );
}

function is_email(email) {
  return /^([\w!.%+\-])+@([\w\-])+(?:\.[\w\-]+)+$/.test(email);
}



  /**************  lib/string/sprintf.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @requires util string-escape
 *  @provides sprintf
 */

/**
 *  Limited implementation of sprintf. Conversions:
 *    %s  A string, which will be HTML escaped.
 *    %d  An integer.
 *    %f  A floating point number.
 *    %q  A quoted string. Like %s, but puts (pretty) quotes around the output.
 *        This is purely a display conversion, it does not render the string
 *        appropriate for output in any specific context. Use %e to generate an
 *        escaped string.
 *    %e  An excaped string which you can embed into HTML as a JS parameter. For
 *        example:
 *
 *          sprintf( '<a onclick="alert(%e);">see message</a>', msg );
 *
 *    %h  An HTML string; it will not be escaped.
 *    %x  An exception.
 *
 *  This "sprintf" now attempts to support some of the fancy options of real
 *  sprintf(), like "%'Q8.8d" to produce a string like "QQQQQQ35". Any
 *  behavioral differences between this sprintf() and real sprintf() should be
 *  considered bugs or deficiencies in this implementation.
 *
 *  These thing still don't work:
 *    - min/max arguments as applied to floating point numbers
 *    - using a '*' for length
 *    - esoteric conversions
 *    - weird positive/negative number formatting
 *    - argument swapping
 *
 *  @author epriestley
 */
function sprintf( ) {

  if (arguments.length == 0) {
    Util.warn(
      'sprintf() was called with no arguments; it should be called with at '   +
      'least one argument.');
    return '';
  }

  var args = [ 'This is an argument vector.' ];
  for ( var ii = arguments.length - 1; ii > 0; ii-- ) {
    if ( typeof( arguments[ii] ) == "undefined" ) {
      Util.log(
        'You passed an undefined argument (argument '+ii+' to sprintf(). '     +
        'Pattern was: `'+(arguments[0])+'\'.',
        'error');
      args.push('');
    } else if (arguments[ii] === null) {
      args.push('');
    } else if (arguments[ii] === true) {
      args.push('true');
    } else if (arguments[ii] === false) {
      args.push('false');
    } else {
      if (!arguments[ii].toString) {
        Util.log(
          'Argument '+(ii+1)+' to sprintf() does not have a toString() '       +
          'method. The pattern was: `'+(arguments[0])+'\'.',
          'error');
        return '';
      }
      args.push(arguments[ii]);
    }
  }

  var pattern = arguments[0];
  pattern = pattern.toString().split('%');
  var patlen = pattern.length;
  var result = pattern[0];
  for (var ii = 1; ii < patlen; ii++) {

    if (args.length == 0) {
      Util.log(
        'Not enough arguments were provide to sprintf(). The pattern was: '    +
        '`'+(arguments[0])+'\'.',
        'error');
      return '';
    }

    if (!pattern[ii].length) {
      result += "%";
      continue;
    }

    var p = 0;
    var m = 0;

    var r = '';

    var padChar  = ' ';
    var padSize  = null;
    var maxSize  = null;
    var rawPad   = '';
    var pos = 0;

    if (m = pattern[ii].match(/^('.)?(?:(-?\d+\.)?(-?\d+)?)/)) {

      if (m[2] !== undefined && m[2].length) {
        padSize = parseInt(rawPad = m[2]);
      }

      if (m[3] !== undefined && m[3].length) {
        if (padSize !== null) {
          maxSize = parseInt(m[3]);
        } else {
          padSize = parseInt(rawPad = m[3]);
        }
      }

      pos = m[0].length;

      if (m[1] !== undefined && m[1].length) {
        padChar = m[1].charAt(1);
      } else {
        if (rawPad.charAt(0) == 0) {
          padChar = '0';
        }
      }
    }

    switch (pattern[ii].charAt(pos)) {
      // A string.
      case 's':
        raw = htmlspecialchars(args.pop( ).toString( ));
        break;
      // HTML.
      case 'h':
        raw = args.pop( ).toString( );
        break;
      // An integer.
      case 'd':
        raw = parseInt(args.pop( )).toString();
        break;
      // A float.
      case 'f':
        raw = parseFloat(args.pop( )).toString();
        break;
      // A quoted something-or-other.
      case 'q':
        raw = "`" + htmlspecialchars(args.pop( ).toString( ))+ "'";
        break;
      // A string parameter.
      case 'e':
        raw = "'" + escape_js_quotes(args.pop( ).toString( )) + "'";
        break;
      // A list parameter.
      case 'L':
        var list = args.pop( );
        for (var ii = 0; ii < list.length; ii++) {
          list[ii] = "`" + htmlspecialchars(args.pop( ).toString( ))+ "'";
        }
        if (list.length > 1) {
          list[list.length - 1] = 'and ' + list[list.length - 1];
        }
        raw = list.join(', ');
        break;
      // An exception.
      case 'x':
        x = args.pop();

        var line = '?';
        var src  = '?';

        try {

          if (typeof(x['line']) != 'undefined') {
            line = x.line;
          } else if (typeof(x['lineNumber']) != 'undefined') {
            line = x.lineNumber;
          }

          if (typeof(x['sourceURL']) != 'undefined') {
            src = x['sourceURL'];
          } else if (typeof(x['fileName']) != 'undefined') {
            src = x['fileName'];
          }

        } catch (exception) {

          //  Ignore the exception; it just means we're trying to get properties
          //  of some "magic" object which resists property access. For one
          //  example of such an object, do:
          //
          //    document.appendChild('some_string')
          //
          //  ...in Firefox. Specifically, Firefox will throw an "exception"
          //  which throws another exception when you try to access its
          //  lineNumber. Good job, Firefox. You're one heckuva browser.

        }

        var s = '[An Exception]';
        try {
          s = x.message || x.toString( );
        } catch (exception) {
          //  Don't care.
        }

        raw = s + ' [at line ' + line + ' in ' + src + ']';
        break;
      // Something we don't recognize.
      default:
        raw = "%" + pattern[ii].charAt(pos+1);
        break;
    }

    if (padSize !== null) {
      if (raw.length < Math.abs(padSize)) {
        var padding = '';
        var padlen  = (Math.abs(padSize)-raw.length);
        for (var ll = 0; ll < padlen; ll++) {
          padding += padChar;
        }

        if (padSize < 0) {
          raw += padding;
        } else {
          raw = padding + raw;
        }
      }
    }

    if (maxSize !== null) {
      if (raw.length > maxSize) {
        raw = raw.substr(0, maxSize);
      }
    }

    result += raw + pattern[ii].substring(pos+1);
  }

  if ( args.length > 1 ) {
    Util.log(
      'Too many arguments ('+(args.length-1)+' extras) were passed to '        +
      'sprintf(). Pattern was: `'+(arguments[0])+'\'.',
      'error');
   }

  return result;
}



  /**************  lib/string/uri.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @requires function-extensions
 *  @provides uri
 */

/**
 *  URI parsing and manipulation. The URI class breaks a URI down into its
 *  component parts and allows you to manipulate and rebuild them. It also
 *  allows you to interconvert query strings and objects, and perform
 *  same-origin analysis and coersion.
 *
 *  To analyze a URI:
 *
 *    var uri = new URI('http://www.facebook.com:1234/asdf.php?a=b#anchor');
 *    uri.getProtocol( );   //  http
 *    uri.getDomain( );     //  www.facebook.com
 *    uri.getPort( );       //  1234
 *    uri.getPath( );       //  asdf.php
 *    uri.getQueryData( );  //  {a:'b'}
 *    uri.getFragment( );   //  anchor
 *
 *  To change a URI:
 *
 *    var uri = new URI('http://www.facebook.com/');
 *    uri.setProtocol('gopher');
 *    uri.toString( );    //  gopher://www.facebook.com/
 *
 *  The `URI' class deals with query data by unserializing it into an object,
 *  which acts as a map from query parameter names to values. Two functions
 *  are provided to allow you to use this facility externally: explodeQuery()
 *  and implodeQuery(). The former converts a query string into an object, and
 *  the latter reverses the transformation.
 *
 *  @task   read          Analyzing a URI
 *  @task   write         Changing URIs
 *  @task   query         Managing Query Strings
 *  @task   sameorigin    Working with the Same Origin Policy
 *
 *  @author epriestley
 */
function /* class */ URI(uri) {
  if (uri === window) {
    Util.error('what the hell are you doing');
    return;
  }

  if (this === window) {
    return new URI(uri||window.location.href);
  }

  this.parse(uri||'');
}

copy_properties(URI, {


  /**
   * Returns a URI object for the current window.location.
   */
  getRequestURI : function() {
    return new URI(window.location.href);
  },


  /**
   *  Regular expression describing a URI.
   *
   *  @access protected
   *  @author epriestley
   */
  expression :
    /(((\w+):\/\/)([^\/:]*)(:(\d+))?)?([^#?]*)(\?([^#]*))?(#(.*))?/,


  /**
   *  Convert an HTTP querystring into a Javascript object. This function
   *  is the inverse of implodeQuery().
   *
   *  Note: this doesn't currently support array query syntax. We haven't
   *  needed it yet; write it if you do.
   *
   *  @param  String  HTTP query string, like 'cow=quack&duck=moo'.
   *  @return Object  Map of query keys to values.
   *
   *  @task   query
   *
   *  @access public
   *  @author epriestley
   */
  explodeQuery : function(q) {
    if (!q) {
      return {};
    }
    var ii,t,r = {}; q=q.split('&');
    for (ii = 0, l = q.length; ii < l; ii++) {
      t = q[ii].split('=');
      r[decodeURIComponent(t[0])] = (typeof(t[1])=='undefined')
        ? ''
        : decodeURIComponent(t[1]);
    }
    return r;
  },


  /**
   *  Convert a Javascript object into an HTTP query string. This function is
   *  the inverse of explodeQuery().
   *
   *  @param  Object  Map of query keys to values.
   *  @return String  HTTP query string, like 'cow=quack&duck=moo'.
   *
   *  @task   query
   *
   *  @access public
   *  @author marcel
   */
  implodeQuery : function(obj, name) {
    name = name || '';

    var r = [];

    if (obj instanceof Array) {
      for (var ii = 0; ii < obj.length; ii++) {
        try {
          r.push(URI.implodeQuery(obj[ii], name ? name+'['+ii+']' : ii));
        } catch (ignored) {
          //  Don't care.
        }
      }
    } else if (typeof(obj) == 'object') {
      if (is_node(obj)) {
        r.push('{node}');
      } else {
        for (var k in obj) {
          try {
            r.push(URI.implodeQuery(obj[k], name ? name+'['+k+']' : k));
          } catch (ignored) {
            //  Don't care.
          }
        }
      }
    } else if (name && name.length) {
      r.push(encodeURIComponent(name)+'='+encodeURIComponent(obj));
    } else {
      r.push(encodeURIComponent(obj));
    }

    return r.join('&');
  }

}); // End URI Static Methods

copy_properties(URI.prototype,{


  /**
   *  Set the object's value by parsing a URI.
   *
   *  @param  String  A URI or URI fragment to parse.
   *  @return this
   *
   *  @task   read
   *
   *  @access public
   *  @author epriestley
   */
  parse : function(uri) {
    var m = uri.toString( ).match(URI.expression);
    copy_properties(this,{
      protocol : m[3]||'',
        domain : m[4]||'',
          port : m[6]||'',
          path : m[7]||'',
         query : URI.explodeQuery(m[9]||''),
      fragment : m[11]||''
    });

    return this;
  },


  /**
   *  Set the protocol for a URI.
   *
   *  @param  String  The new protocol.
   *  @return this
   *
   *  @task   write
   *
   *  @access public
   *  @author epriestley
   */
  setProtocol : function(p) {
    this.protocol = p;
    return this;
  },


  /**
   *  Get the protocol of a URI.
   *
   *  @return String  The current protocol.
   *
   *  @task   read
   *
   *  @access public
   *  @author epriestley
   */
  getProtocol : function( ) {
    return this.protocol;
  },


  /**
   *  Replace existing query data with new query data.
   *
   *  @param  Object  Map of query data.
   *  @return this
   *
   *  @task   write
   *
   *  @access public
   *  @author epriestley
   */
  setQueryData : function(o) {
    this.query = o;
    return this;
  },


  /**
   *  Adds some data to the query string of a URI. Note that if you provide
   *  the same key twice, this function will overwrite the old value. This
   *  is a generally useful behavior and makes implementation trivial, but it
   *  makes it technically impossible to construct all legal query strings.
   *
   *  @param  Object  A map of query keys to values.
   *  @return this
   *
   *  @task   write
   *
   *  @access public
   *  @author epriestley
   */
  addQueryData : function(o) {
    return this.setQueryData(copy_properties(this.query, o));
  },


  /**
   *  Retrieves a URI's query data as an object. Use implodeQuery to convert
   *  this to a query string, if necessary.
   *
   *  @return Object  A map of query keys to values.
   *
   *  @task   read
   *
   *  @access public
   *  @author epriestley
   */
  getQueryData : function( ) {
    return this.query;
  },


  /**
   *  Set the fragment of a URI.
   *
   *  @param  String  The new fragment.
   *  @return this
   *
   *  @task   write
   *
   *  @access public
   *  @author epriestley
   */
  setFragment : function(f) {
    this.fragment = f;
    return this;
  },


  /**
   *  Get the (possibly empty) fragment of a URI.
   *
   *  @return String  The current fragment.
   *
   *  @task   read
   *
   *  @access public
   *  @author epriestley
   */
  getFragment : function( ) {
    return this.fragment;
  },


  /**
   *  Set the domain of a URI.
   *
   *  @param  String  The new domain.
   *  @return this
   *
   *  @task   write
   *
   *  @access public
   *  @author epriestley
   */
  setDomain : function(d) {
    this.domain = d;
    return this;
  },


  /**
   *  Get the domain of a URI.
   *
   *  @return String  The current domain.
   *
   *  @task   read
   *
   *  @access public
   *  @author epriestley
   */
  getDomain : function( ) {
    return this.domain;
  },


  /**
   *  Set the port of a URI.
   *
   *  @param  Number  New port number.
   *  @return this
   *
   *  @task   write
   *
   *  @access public
   *  @author epriestley
   */
  setPort : function(p) {
    this.port = p;
    return this;
  },


  /**
   *  Retrieve the port component (which may be empty) of a URI. This will
   *  only give you explicit ports, so you won't get `80' back from a URI like
   *  `http://www.facebook.com/'.
   *
   *  @return String  The current port.
   *
   *  @task   read
   *
   *  @access public
   *  @author epriestley
   */
  getPort : function( ) {
    return this.port;
  },


  /**
   *  Set the path component of a URI.
   *
   *  @param  String  The new path.
   *  @return this
   *
   *  @task   write
   *
   *  @access public
   *  @author epriestley
   */
  setPath : function(p) {
    this.path = p;
    return this;
  },


  /**
   *  Retrieve the path component of a URI (which may be empty).
   *
   *  @return String  The current path.
   *
   *  @task   read
   *
   *  @access public
   *  @author epriestley
   */
  getPath : function( ) {
    return this.path;
  },


  /**
   *  Convert the URI object to a URI string.
   *
   *  @return String  The URI as a string.
   *
   *  @task   read
   *
   *  @access public
   *  @author epriestley
   */
  toString : function( ) {

    var r = '';
    var q = URI.implodeQuery(this.query);

    this.protocol && (r += this.protocol + '://');
    this.domain   && (r += this.domain);
    this.port     && (r += ':' + this.port);

    if (this.domain && !this.path) {
      r += '/';
    }

    this.path     && (r += this.path);
    q             && (r += '?' + q);
    this.fragment && (r += '#' + this.fragment);

    return r;
  },


  /**
   * Returns another URI object that contains only the path, query string,
   * and fragment.
   *
   * @author jrosenstein
   */
  getUnqualifiedURI : function() {
    return new URI(this).setProtocol(null).setDomain(null).setPort(null);
  },


  /**
   * Converts a URI like '/profile.php' into 'http://facebook.com/profile.php'.
   * If the URI already has a domain, then just returns a copy of this.
   *
   * @author jrosenstein
   */
  getQualifiedURI : function() {
    var current = URI();
    var uri = new URI(this);
    if (!uri.getDomain()) {
      uri.setProtocol(current.getProtocol())
         .setDomain(current.getDomain())
         .setPort(current.getPort());
    }
    return uri;
  },


  /**
   *  Check if two URIs belong to the same origin, so that making an XMLHTTP
   *  request from one to the other would satisfy the Same Origin Policy. This
   *  function will assume that URIs which fail to specify a domain or protocol
   *  have the effective correct same-origin value.
   *
   *  @param  URI|String  Optionally, a URI to compare the origin of the caller
   *                      to. If none is provided, the current window location
   *                      will be used.
   *  @return bool        True if the caller has the same origin as the target.
   *
   *  @task   sameorigin
   *
   *  @access public
   *  @author epriestley
   */
  isSameOrigin : function(asThisURI) {
    var uri = asThisURI || window.location.href;
    if (!(uri instanceof URI)) {
      uri = new URI(uri.toString());
    }

    if (this.getProtocol() && this.getProtocol() != uri.getProtocol()) {
      return false;
    }

    if (this.getDomain() && this.getDomain() != uri.getDomain()) {
      return false;
    }

    return true;
  },


  /**
   *  For some URIs, we can coerce them so they satisfy the same origin policy.
   *  For example, `college-a.facebook.com' can safely be converted to a request
   *  to `college-b.facebook.com'. This function attempts to coerce a URI so
   *  that it satisfies the same origin policy.
   *
   *  This function will never coerce protocols, so a HTTPS URI can never be
   *  coerced into an HTTP URI. This is almost certainly the best behavior, but
   *  we may have some cases where we actually do need to do this.
   *
   *  @param  URI|String  Optionally, a target URI to try to coerce this URI
   *                      into having the same origin as. If none is provided
   *                      the current window location will be used.
   *  @return bool        True if the caller has been coerced to the same origin
   *                      as the target.
   *
   *  @task   sameorigin
   *
   *  @access public
   *  @author epriestley
   */
  coerceToSameOrigin : function(targetURI) {
    var uri = targetURI || window.location.href;
    if (!(uri instanceof URI)) {
      uri = new URI(uri.toString( ));
    }

    if (this.isSameOrigin(uri)) {
      return true;
    }

    if (this.getProtocol() != uri.getProtocol()) {
      return false;
    }

    var dst = uri.getDomain().split('.');
    var src = this.getDomain().split('.');

    if (dst.pop( ) == 'com' && src.pop( ) == 'com') {
      if (dst.pop( ) == 'facebook' && src.pop( ) == 'facebook') {

        //  Possibly, we need special casing here for some domains which we
        //  won't be able to coerce, like `m', `register', etc.

        this.setDomain(uri.getDomain( ));
        return true;
      }
    }

    return false;
  }

}); // End URI Methods



  /**************  lib/util/util.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @requires sprintf string-extensions
 *  @provides util env
 */


function env_get(k) {
  return typeof(window['Env']) != 'undefined' && Env[k];
}



var Util = {

  fallbackErrorHandler : function(msg) {
    alert(msg);
  },

  isDevelopmentEnvironment : function( ) {
    return env_get('dev');
  },

  warn : function( ) {
    Util.log(sprintf.apply(null, arguments), 'warn');
  },

  error : function( ) {
    Util.log(sprintf.apply(null, arguments), 'error');
  },

  log : function( msg, type ) {
    if (Util.isDevelopmentEnvironment( )) {

      var written = false;

      if (typeof(window['TabConsole']) != 'undefined') {
        var con = TabConsole.getInstance( );
        if (con) {
          con.log(msg, type);
          written = true;
        }
      }

      if (typeof(console) != "undefined" && console.error) {
        console.error(msg);
        written = true;
      }

      if (!written && type != 'deprecated' && Util.fallbackErrorHandler) {
        Util.fallbackErrorHandler(msg);
      }

    } else {
      if (type == 'error') {
        msg += '\n\n' + Util.stack();
        (typeof(window['Env']) != 'undefined') &&
        (Env.rlog) &&
        (typeof(window['debug_rlog']) == 'function') &&
        debug_rlog(msg);
      }
    }
  },

  deprecated : function(what) {
    if (!Util._deprecatedThings[ what ]) {
      Util._deprecatedThings[ what ] = true;

      var msg = sprintf(
        'Deprecated: %q is deprecated.\n\n%s',
        what,
        Util.whyIsThisDeprecated(what));

      Util.log(msg, 'deprecated');
    }
  },

  stack : function() {
    try {
      try {
        // Induce an error
        ({}).llama();
      } catch(e) {
        // If e.stack exists it's probably Firefox and there's a nice stack trace with line numbers waiting for us
        if (e.stack) {
          var stack = [];
          var trace = [];
          var regex = /^([^@]+)@(.+)$/mg;
          var line = regex.exec(e.stack);
          do {
            stack.push([line[1], line[2]]);
          } while (line = regex.exec());
          for (var i = 0; i < stack.length; i++) {
            trace.push('#' + i + ' ' + stack[i][0] + ' @ ' + (stack[i+1] ? stack[i+1][1] : '?'));
          }
          return trace.join('\n');
        // Otherwise we have to build our own...
        } else {
          var trace = [];
          var pos = arguments.callee;
          var stale = [];
          while (pos) {
            // Check to make sure we're not caught in a loop here...
            for (var i = 0; i < stale.length; i++) {
              if (stale[i] == pos) {
                trace.push('#' + trace.length + ' ** recursion ** @ ?');
                return trace.join('\n');
              }
            }
            stale.push(pos);

            // Convert the arguments into a string
            var args = [];
            for (var i = 0; i < pos.arguments.length; i++) {
              if (pos.arguments[i] instanceof Function) {
                var func = /function ?([^(]*)/.exec(pos.arguments[i].toString()).pop();
                args.push(func ? func : 'anonymous');
              } else if (pos.arguments[i] instanceof Array) {
                args.push('Array');
              } else if (pos.arguments[i] instanceof Object) {
                args.push('Object');
              } else if (typeof pos.arguments[i] == 'string') {
                args.push('"' + pos.arguments[i].replace(/("|\\)/g, '\\$1') + '"');
              } else {
                args.push(pos.arguments[i]);
              }
            }
            trace.push('#' + trace.length + ' ' + /function ?([^(]*)/.exec(pos).pop() + '(' + args.join(', ') + ') @ ?');
            if (trace.length>100)break;
            pos = pos.caller;
          }
          return trace.join('\n');
        }
      }
    } catch(e) {
      return 'No stack trace available';
    }
  },

  whyIsThisDeprecated : function(what) {
    return Util._deprecatedBecause[what.toLowerCase( )] ||
          'No additional information is available about this deprecation.';
  },

  _deprecatedBecause : {},
  _deprecatedThings  : {}
};



  /**************  lib/util/configurable.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides configurable
 *  @requires util
 */

/**
 *  Interface for configurable objects.
 *
 *  @author epriestley
 */
var /* interface */ Configurable = {
  getOption : function(opt) {
    if (typeof(this.option[opt]) == 'undefined' ) {
      Util.warn(
        'Failed to get option %q; it does not exist.',
        opt);
      return null;
    }
    return this.option[opt];
  },

  setOption : function(opt, v) {
    if (typeof(this.option[opt]) == 'undefined' ) {
      Util.warn(
        'Failed to set option %q; it does not exist.',
        opt);
    } else {
      this.option[opt] = v;
    }

    return this;
  },

  getOptions : function( ) {
    return this.option;
  }

};


  /**************  lib/math/vector.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @requires event-extensions
 *  @provides vector
 */

/**
 *  A two-dimensional (x,y) vector which belongs to some coordinate domain.
 *  This class provides a consistent, reliable mechanism for acquiring,
 *  manipulating, and acting upon position and dimension informations within
 *  a rendered document.
 *
 *  All vectors are fourth-quadrant with an inverted "Y" axis -- that is, (0,0)
 *  is the upper left corner of the relevant coordinate system, and increasing
 *  X and Y values represent points farther toward the right and bottom,
 *  respectively.
 *
 *  Vectors belong to one of three coordinate domains:
 *
 *    pure
 *      A pure vector is a raw numeric vector which does not exist in any
 *      coordinate system. It has some X and Y coordinate, but does not
 *      represent any position on a rendered canvas.
 *
 *    document
 *      A document vector represents a position on a rendered canvas relative
 *      to the upper left corner of the canvas itself -- that is, the entire
 *      renderable area of the canvas, including parts which may not currently
 *      be visible because of the scroll position. The canvas point represented
 *      by a document vector is not affected by scrolling.
 *
 *    viewport
 *      A viewport vector represents a position on the visible area of the
 *      canvas, relative to the upper left corner of the current scroll area.
 *      That is, (0, 0) is the top left visible point, but not necessarily
 *      the top left point in the document (for instance, if the user has
 *      scrolled down the page). Note that vectors in the viewport coordinate
 *      system may legitimately contain negative elements; they represent
 *      points above and/or to the left of the visible area of the document.
 *
 *
 *  When you acquire a position vector, e.g. with Vector2.getEventPosition(),
 *  you MUST provide a coordinate system to represent it in. Methods which act
 *  on vectors MUST first convert them to the expected coordinate system.
 *  Following these rules consistently will prevent code from exhibiting
 *  unexpected behaviors which are a function of the scroll position.
 *
 *  @task canvas Getting Canvas and Event Vectors
 *  @task vector Manipulating Vectors
 *  @task convert Converting Vector Coordinate Domains
 *  @task actions Performing Actions with Vectors
 *  @task internal Internal
 *
 *  @author epriestley
 */
function /* class */ Vector2( x, y, domain) {
  copy_properties(this, {
         x : parseFloat(x),
         y : parseFloat(y),
    domain : domain || 'pure'
  });
};

copy_properties(Vector2.prototype, {


  /**
   *  Convert a vector into a string.
   *
   *  @task vector
   *  @access public
   *  @author epriestley
   */
  toString : function( ) {
    return '('+this.x+', '+this.y+')';
  },


  /**
   *  Add a vector to the caller, returning a new vector. You may pass either
   *  a Vector2, or (x, y) coordinates as numbers:
   *
   *    var u = new Vector2(1, 2);
   *    u.add(new Vector2(2, 3));   // Fine.
   *    u.add(2, 3);                // Also fine.
   *
   *  The resulting vector will have the same coordinate system as the calling
   *  vector!
   *
   *  @param  Vector2|int Vector2, or the X component of a vector.
   *  @param  null|int    Nothing (if specifying a vector) or the Y component of
   *                      a vector.
   *
   *  @returns Vector2 Vectors  sum of the caller and argument.
   *
   *  @task vector
   *  @access public
   *  @author epriestley
   */
  add : function(vx, vy) {

    var x = this.x,
        y = this.y,
        l = arguments.length;

    if (l == 1) {
      if (vx.domain != 'pure') {
        vx = vx.convertTo(this.domain);
      }
      x += vx.x;
      y += vx.y;
    } else if (l == 2) {
      x += parseFloat(vx);
      y += parseFloat(arguments[1]);
    } else {
      Util.warn(
        'Vector2.add called with %d arguments, should be one (a vector) or '   +
        'two (x and y coordinates).',
        l);
    }

    return new Vector2(x, y, this.domain);
  },


  /**
   *  Multiply a vector by a single scalar, or two scalar components.
   *
   *    vect.mul(3);    //  Scale the vector 3x.
   *    vect.mul(1, 2); //  Scale `y' only, by 2x.
   *    vect.mul(1, 0); //  Isolate the `x' component of a vector.
   *
   *  @param  Number    A scalar value to multiply the x coordinate by, or, if
   *                    only one scalar is provided, the x and y coordinates.
   *  @param  Number    An optional scalar to multiply the y coordinate by.
   *
   *  @return Vector2   A result vector.
   *
   *  @task   vector
   *  @access public
   *  @author epriestley
   */
  mul : function(sx, sy) {
    if (typeof(sy) == "undefined") {
      sy = sx;
    }

    return new Vector2(this.x*sx, this.y*sy, this.domain);
  },


  /**
   *  Subtract a vector from the caller, returning a new vector. You may pass
   *  either a Vector2, or (x, y) coordinates as numbers. The resulting vector
   *  will have the same coordinate system as the calling vector!
   *
   *  @task vector
   *  @access public
   *  @author epriestley
   */
  sub : function(v) {
    var x = this.x,
        y = this.y,
        l = arguments.length;

    if (l == 1) {
      if (v.domain != 'pure') {
        v = v.convertTo(this.domain);
      }
      x -= v.x;
      y -= v.y;
    } else if (l == 2) {
      x -= parseFloat(v);
      y -= parseFloat(arguments[1]);
    } else {
      Util.warn(
        'Vector2.add called with %d arguments, should be one (a vector) or '   +
        'two (x and y coordinates).',
        l);
    }

    return new Vector2(x, y, this.domain);
  },


  /**
   *  Return the distance between two vectors.
   *
   *  @task vector
   *  @access public
   *  @author epriestley
   */
  distanceTo : function(v) {
    return this.sub(v).magnitude( );
  },


  /**
   *  Return the magnitude (length) of a vector.
   *
   *  @task vector
   *  @access public
   *  @author epriestley
   */
  magnitude : function( ) {
    return Math.sqrt((this.x*this.x) + (this.y*this.y));
  },


  /**
   *  Convert a vector to viewport coordinates.
   *
   *  @task convert
   *  @access public
   *  @author epriestley
   */
  toViewportCoordinates : function( ) {
    return this.convertTo( 'viewport' );
  },


  /**
   *  Convert a vector to document coordinates.
   *
   *  @task convert
   *  @access public
   *  @author epriestley
   */
  toDocumentCoordinates : function( ) {
    return this.convertTo( 'document' );
  },


  /**
   *  Convert a vector to the specified coordinate system. `viewport' and
   *  `document' vectors may be freely converted, and any vector may be
   *  converted to its own domain or to the `pure' domain. However, it is
   *  impossible to convert a `pure' vector into either the `viewport' or
   *  `document' coordinate systems.
   *
   *  @task convert
   *  @access public
   *  @author epriestley
   */
  convertTo : function(newDomain) {

    if (newDomain != 'pure'     &&
        newDomain != 'viewport' &&
        newDomain != 'document') {
      Util.error(
        'Domain %q is not valid; legitimate coordinate domains are %q, %q, '   +
        '%q.',
        newDomain,
        'pure',
        'viewport',
        'document');
      return new Vector2(0, 0);
    }

    if (newDomain == this.domain) {
      return new Vector2(this.x, this.y, this.domain);
    }

    if (newDomain == 'pure') {
      return new Vector2(this.x, this.y);
    }

    if (this.domain == 'pure') {
      Util.error(
        'Unable to covert a pure vector to %q coordinates; a pure vector is '  +
        'abstract and does not exist in any document coordinate system. If '   +
        'you need to hack around this, create the vector explicitly in some '  +
        'document coordinate domain, by passing a third argument to the '      +
        'constructor. But you probably don\'t, and are just using the class '  +
        'wrong. Stop doing that.',
        newDomain);
      return new Vector2(0, 0);
    }

    // Note that we can't use add/sub here because they call convertTo and
    // we end up with a big mess.
    var o = Vector2.getScrollPosition('document');
    var x = this.x, y = this.y;
    if (this.domain == 'document') {
      // Convert document coords to viewport coords by subtracting the scroll
      // position. This can produce negative values, because document
      // coordinates could be above or to the left of the viewport.
      x -= o.x;
      y -= o.y;
    } else {
      // Convert viewport coords to document coords by adding the scroll
      // position. This can not produce negative values.
      x += o.x;
      y += o.y;
    }

    return new Vector2(x, y, newDomain);
  },

  /**
   *  Set an element's position to the vector position. This is a convenience
   *  method for setting the `top' and `left' style properties of a DOM
   *  element.
   *
   *  @task actions
   *
   *  @param  Node A DOM element to reposition.
   *  @return this
   *
   *  @author epriestley
   */
  setElementPosition : function(el) {
    var p = this.convertTo('document');
    el.style.left = parseInt(p.x) + 'px';
    el.style.top  = parseInt(p.y) + 'px';

    return this;
  },


  /**
   *  Set an element's dimensions to the vector size. This is a convenience
   *  method for setting the `width' and `height' style properties of a DOM
   *  element.
   *
   *  @task actions
   *
   *  @param Node A DOM element to resize.
   *  @return this
   *
   *  @author epriestley
   */
  setElementDimensions : function(el) {
    el.style.width  = parseInt(this.x) + 'px';
    el.style.height = parseInt(this.y) + 'px';

    return this;
  },

  setElementWidth : function(el) {
    el.style.width  = this.x + 'px';

    return this;
  }

}); // End Vector2 Methods



copy_properties(Vector2, {

  compass : {
         east : 'e',
         west : 'w',
        north : 'n',
        south : 's',
       center : 'center',
    northeast : 'ne',
    northwest : 'nw',
    southeast : 'se',
    southwest : 'sw'
  },


  /**
   *  Throw a domain error.
   *
   *  @task internal
   *
   *  @access protected
   *  @author epriestley
   */
  domainError : function( ) {
    Util.error(
      'You MUST provide a coordinate system domain to Vector2.* functions. '   +
      'Available domains are %q and %q. See the documentation for more '       +
      'information.',
      'document',
      'viewport');
  },


  /**
   *  Returns the position of the event (generally, a mouse event) in the
   *  specified domain's coordinate system.
   *
   *  @task canvas
   *  @author epriestley
   */
  getEventPosition : function(e, domain) {
    domain = domain || 'document';
    e = event_get(e);

    var x = e.pageX || (e.clientX +
            (document.documentElement.scrollLeft || document.body.scrollLeft));
    var y = e.pageY || (e.clientY +
            (document.documentElement.scrollTop || document.body.scrollTop));

    return (new Vector2(x, y, 'document')
      .convertTo(domain));
  },


  /**
   *  Returns the current scroll position, in the specified domain's coordinate
   *  system. Note that the scroll position is ALWAYS (0,0) in the viewport
   *  coordinate system, by definition.
   *
   *  @task canvas
   *  @author epriestley
   */
  getScrollPosition : function(domain) {
    domain = domain || 'document';

    var x = document.body.scrollLeft || document.documentElement.scrollLeft;
    var y = document.body.scrollTop  || document.documentElement.scrollTop;

    return (new Vector2(x, y, 'document').convertTo(domain));
  },


  /**
   *  Returns an element's position, in the specified coordinate system. The
   *  returned vector represents the position of its top left point.
   *
   *  @task canvas
   *  @author epriestley
   */
  getElementPosition : function(el, domain) {
    domain = domain || 'document';

    return (new Vector2(elementX(el), elementY(el), 'document')
      .convertTo(domain));
  },


  /**
   *  Returns the dimensions of an element (dimension vectors are pure vectors
   *  and do not require a domain).
   *
   *  @task canvas
   *  @author epriestley
   */
  getElementDimensions : function(el) {

    //  Safari can't figure out the dimensions of a table row, so derive them
    //  from the corners of the first and last cells. This should really grab
    //  TH's, too.

    if (ua.safari() && el.nodeName == 'TR') {
      var tds = el.getElementsByTagName('td');
      var dimensions =
        Vector2
          .getElementCompassPoint(
            tds[tds.length-1],
            Vector2.compass.southeast)
          .sub(Vector2.getElementPosition(tds[0]));

      return dimensions;
    }

    var x = el.offsetWidth   || 0;
    var y = el.offsetHeight  || 0;

    return new Vector2(x, y);
  },


  /**
   *  Returns a compass point on an element. Valid compass points live in
   *  Vector2.compass, and are: northwest, northeast, southeast, southwest,
   *  center, north, east, south, and west.
   *
   *    Vector2.getElementCompassPoint(element, Vector2.compass.northeast);
   *
   *
   *  @param    Element   Element to get the compass point of.
   *  @param    enum      Compass point to retrieve, defined in
   *                      Vector2.compass.
   *
   *  @return   Vector2   The specified compass point of the element.
   *
   *  @task     canvas
   *  @access   public
   *  @author   epriestley
   */
  getElementCompassPoint : function(el, which) {
    which = which || Vector2.compass.southeast;

    var p = Vector2.getElementPosition(el);
    var d = Vector2.getElementDimensions(el);
    var c = Vector2.compass;

    switch (which) {
      case c.east:        return p.add(d.x, d.y*.5);
      case c.west:        return p.add(0, d.y*.5);
      case c.north:       return p.add(d.x*.5, 0);
      case c.south:       return p.add(d.x*.5, d.y);
      case c.center:      return p.add(d.mul(.5));
      case c.northwest:   return p;
      case c.northeast:   return p.add(d.x, 0);
      case c.southwest:   return p.add(0, d.y);
      case c.southeast:   return p.add(d);
    }

    Util.error('Unknown compass point %s.', which);

    return p;
   },


  /**
   *  Returns the dimensions of the viewport (that is, the area of the window
   *  in which page content is visible). Dimension vectors are `pure' vectors
   *  and do not belong to document or viewport domains.
   *
   *  @task canvas
   *  @author epriestley
   */
  getViewportDimensions : function( ) {

    var x =
      (window && window.innerWidth)                                           ||
      (document && document.documentElement
                && document.documentElement.clientWidth)                      ||
      (document && document.body && document.body.clientWidth)                ||
      0;

    var y =
      (window && window.innerHeight)                                          ||
      (document && document.documentElement
                && document.documentElement.clientHeight)                     ||
      (document && document.body && document.body.clientHeight)               ||
      0;

    return new Vector2(x, y);
  },


  /**
   *  Returns the dimensions of the entire document canvas. This includes
   *  whatever page content may not be visible in the current viewport. Like all
   *  dimension vectors, this one exists in the `pure' coordinate system.
   *
   *  @task canvas
   *  @author epriestley
   */
  getDocumentDimensions : function( ) {
    var x =
      (document && document.body && document.body.scrollWidth)                ||
      (document && document.documentElement
                && document.documentElement.scrollWidth)                      ||
      0;

    var y =
      (document && document.body && document.body.scrollHeight)               ||
      (document && document.documentElement
                && document.documentElement.scrollHeight)                     ||
      0;

    return new Vector2(x, y);
  },


  /**
   *  Scroll the document to the specified position.
   *
   *  This could probably be put somewhere better. It would be really nice to
   *  tween this, too, but I'm not going to touch it for now.
   *
   *  @param Vector2 Position to scroll to.
   *
   *  @task actions
   *  @author epriestley
   */
  scrollTo : function(v) {
    if (!(v instanceof Vector2)) {
      v = new Vector2(
        Vector2.getScrollPosition( ).x,
        Vector2.getElementPosition($(v)).y,
        'document');
    }

    v = v.toDocumentCoordinates( );
    if (window.scrollTo) {
      window.scrollTo(v.x, v.y);
    }
  }

}); // End Vector2 Static Methods


var mouseX            = function(e) { return Vector2.getEventPosition(e).x; }
var mouseY            = function(e) { return Vector2.getEventPosition(e).y; }
var pageScrollX       = function() { return Vector2.getScrollPosition().x; }
var pageScrollY       = function() { return Vector2.getScrollPosition().y; }
var getViewportWidth  = function() { return Vector2.getViewportDimensions().x; }
var getViewportHeight = function() { return Vector2.getViewportDimensions().y; }

// Used to fix Opera bug 165620, "scrollLeft, scrollTop on inline elements
// return distances from edges of viewport (transmenu)" (fixed in Opera 9.5).
var operaIgnoreScroll = {'table': true, 'inline-table': true, 'inline': true};

function elementX(obj) {

  if (ua.safari() < 500 && obj.tagName == 'TR') {
    obj = obj.firstChild;
  }

  var left = obj.offsetLeft;
  var op = obj.offsetParent;

  while (obj.parentNode && document.body != obj.parentNode) {
    obj = obj.parentNode;
    if (!(ua.opera() < 9.50) || !operaIgnoreScroll[window.getComputedStyle(obj, '').getPropertyValue('display')]) {
      left -= obj.scrollLeft;
    }
    if (op == obj) {
      // Safari 2.0 doesn't support offset* for table rows
      if (ua.safari() < 500 && obj.tagName == 'TR') {
        left += obj.firstChild.offsetLeft;
      } else {
        left += obj.offsetLeft;
      }
      op = obj.offsetParent;
    }
  }
  return left;
}

function elementY(obj) {

  if (ua.safari() < 500 && obj.tagName == 'TR') {
    obj = obj.firstChild;
  }

  var top = obj.offsetTop;
  var op = obj.offsetParent;
  while (obj.parentNode && document.body != obj.parentNode) {
    obj = obj.parentNode;
    if (!isNaN(obj.scrollTop)) {
      if (!(ua.opera() < 9.50) || !operaIgnoreScroll[window.getComputedStyle(obj, '').getPropertyValue('display')]) {
        top -= obj.scrollTop;
      }
    }
    if (op == obj) {
      // Safari 2.0 doesn't support offset* for table rows
      if (ua.safari() < 500 && obj.tagName == 'TR') {
        top += obj.firstChild.offsetTop;
      } else {
        top += obj.offsetTop;
      }
      op = obj.offsetParent;
    }
  }
  return top;
}



  /**************  lib/math/rect.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @requires vector
 *  @provides rect
 */

/**
 *  A companion class to Vector2, Rect provides various methods for working
 *  with rectangular areas on screen. This class behaves in a method
 *  substantially similar to Vector2.
 *
 *  @author epriestley
 */
function /* class */ Rect(t, r, b, l, domain) {
  copy_properties(this, {
         t : t,
         r : r,
         b : b,
         l : l,
    domain : domain || 'pure'
  });
};

copy_properties(Rect.prototype, {

  w : function( ) { return this.r - this.l; },
  h : function( ) { return this.b - this.t; },

  area : function( ) {
    return this.w( ) * this.h( );
  },

  toString : function( ) {
    return '(('+this.l+', '+this.t+'), ('+this.r+', '+this.b+'))';
  },

  /**
   *  Returns true if the calling Rect intersects the argument Rect at all,
   *  even on an edge.
   */
  intersects : function(v) {
    v = v.convertTo(this.domain);
    var u = this;
    if (u.l > v.r || v.l > u.r || u.t > v.b || v.t > u.b) {
      return false;
    }
    return true;
  },

  /**
   *  Returns the intersecting area of two rectangles.
   */
  intersectingArea : function(v) {
    v = v.convertTo(this.domain);
    var u = this;

    if (!this.intersects(v)) {
      return null;
    }

    return new Rect(
      Math.max(u.t, v.t),
      Math.min(u.r, v.r),
      Math.min(u.b, v.b),
      Math.max(u.l, v.l)).area( );
  },

  /**
   *  Returns true if the caller completely contains the argument.
   */
  contains : function(v) {
    v = v.convertTo(this.domain);
    var u = this;

    if (v instanceof Vector2) {
      return (u.l <= v.x && u.r >= v.x && u.t <= v.y && u.b >= v.y);
    } else {
      return (u.l <= v.l && u.r >= u.r && u.t <= v.t && u.b >= v.b);
    }
  },

  /**
   *  Returns true if the caller is physically large enough in width and
   *  height to possibly contain the argument.
   */
  canContain : function(v) {
    v = v.convertTo(this.domain);
    return (v.h() <= this.h()) && (v.w() <= this.w());
  },

  /**
   *  If the caller and argument intersect, the caller will be shifted down
   *  vertically until it no longer intersects.
   */
  forceBelow : function(v, min) {
    min = min || 0;
    v = v.convertTo(this.domain);
    if (v.b > this.t) {
      return this.offset(0, (v.b - this.t) + min);
    }
    return this;
  },

  offset : function(x, y) {
    return new Rect(this.t+y, this.r+x, this.b+y, this.l+x, this.domain);
  },

  expand : function(x, y) {
    return new Rect(this.t, this.r+x, this.b+y, this.l, this.domain);
  },

  scale : function(x, y) {
    y = y || x;
    return new Rect(
      this.t,
      this.l+(this.w( )*x),
      this.t+(this.h( )*y),
      this.l,
      this.domain);
  },


  /**
   *  Change the size of a Rect without changing its position.
   */
  setDimensions : function(x, y) {
    return new Rect(
      this.t,
      this.l+x,
      this.t+y,
      this.l,
      this.domain);
  },

  /**
   *  Change the location of a Rect without changing its size.
   */
  setPosition : function(x, y) {
    return new Rect(
      x,
      this.w( ),
      this.h( ),
      y,
      this.domain);
  },

  boundWithin : function(v) {
    if (v.contains(this) || !v.canContain(this)) {
      return this;
    }

    var x = 0, y = 0;
    if (this.l < v.l) {
      x = v.l - this.l;
    } else if (this.r > v.r) {
      x = v.r - this.r;
    }

    if (this.t < v.t) {
      y = v.t - this.t;
    } else if (this.b > v.b) {
      y = v.b - this.b;
    }

    return this.offset(x, y);
  },

  setElementBounds : function(el) {
    this.getPositionVector( ).setElementPosition(el);
    this.getDimensionVector( ).setElementDimensions(el);
    return this;
  },

  getPositionVector : function( ) {
    return new Vector2(this.l, this.t, this.domain);
  },

  getDimensionVector : function( ) {
    return new Vector2(this.w( ), this.h( ), 'pure');
  },

  convertTo : function(newDomain) {
    if (this.domain == newDomain) {
      return this;
    }

    if (newDomain == 'pure') {
      return new Rect(this.t, this.r, this.b, this.l, 'pure');
    }

    if (this.domain == 'pure') {
      Util.error(
        'Unable to convert a pure rect to %q coordinates.',
        newDomain);
      return new Rect(0, 0, 0, 0);
    }

    var p = new Vector2(this.l, this.t, this.domain).convertTo(newDomain);

    return new Rect(p.y, p.x+this.w( ), p.y+this.h( ), p.x, newDomain);
  },

  constrict : function(x, y) {

    if (typeof(y) == 'undefined') {
      y = x;
    }

    x = x || 0;

    return new Rect(this.t + y, this.r - x, this.b - y, this.l + x, this.domain);
  },

  expandX : function( ) {
    return new Rect(this.t, Number.POSITIVE_INFINITY, this.b, Number.NEGATIVE_INFINITY);
  },

  expandY : function( ) {
    return new Rect(number.NEGATIVE_INFINITY, this.r, Number.POSITIVE_INFINITY, this.l);
  }


});


copy_properties(Rect, {
  newFromVectors : function(pos, dim) {
    return new Rect(pos.y, pos.x+dim.x, pos.y+dim.y, pos.x, pos.domain);
  },

  getElementBounds : function(el) {
    return Rect.newFromVectors(
      Vector2.getElementPosition(el),
      Vector2.getElementDimensions(el));
  },

  getViewportBounds : function( ) {
    return Rect.newFromVectors(
      Vector2.getScrollPosition(),
      Vector2.getViewportDimensions());
  },

  getDocumentBounds : function( ) {
    return Rect.newFromVectors(
      new Vector2(0, 0, 'document'),
      Vector2.getDocumentDimensions( ));
  }

});


  /**************  lib/math/extensions.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 * Some useful math-related helper functions.
 *
 * @author jwiseman
 * @provides math-extensions
 */

function rand32() {
  return Math.floor(Math.random()*4294967295);
}




  /**************  base.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides legacy-base sound
 *  @requires control-textinput control-textarea control-dom dom css link-controller
 */

//  Really primitive shield against double-inclusion. This could be much more
//  sophisticated, but the problem is fairly rare; we mostly just want to be
//  less mysterious about it than having string.split recurse indefinitely.
try {
  if (window.fbJavascriptLibrariesHaveLoaded) {
    Util.error(
      'You have double-included base.js and possibly other Javascript files; ' +
      'it may be in a package. This will cause you great unhappiness. Each '   +
      'file should be included at most once.');
  }
  window.fbJavascriptLibrariesHaveLoaded = true;
} catch(ignored) { }

function gen_unique() {
  return ++gen_unique._counter;
}
gen_unique._counter = 0;

function close_more_list() {
  var list_expander = ge('expandable_more');
  if (list_expander) {
    list_expander.style.display = 'none';
    removeEventBase(document, 'click', list_expander.offclick, list_expander.id);
  }

  var sponsor = ge('ssponsor');
  if (sponsor) {
    sponsor.style.position = '';
  }

  var link_obj= ge('more_link');
  if (link_obj) {
    link_obj.innerHTML = tx('base01');
    link_obj.className = 'expand_link more_apps';
  }
}


function expand_more_list() {

  var list_expander = ge('expandable_more');

  // remove highlight if there is one
  var more_link = ge('more_section');
  if (more_link) {
    remove_css_class_name(more_link, 'highlight_more_link');
  }

  if (list_expander) {
    list_expander.style.display = 'block';
    list_expander.offclick = function(e) {
      if (!is_descendent(event_get_target(e), 'sidebar_content')) {
        close_more_list();
      }
    }.bind(list_expander);

    addEventBase(document, 'click', list_expander.offclick, list_expander.id);
  }

  var sponsor =  ge('ssponsor');
  if (sponsor) {
    sponsor.style.position = 'static';
  }

  var link_obj= ge('more_link');
  if (link_obj) {
    link_obj.innerHTML = tx('base02');
    link_obj.className = 'expand_link less_apps';
  }
}


function create_hidden_input(name, value) {
  return $N('input', {name: name, id: name, value: value, type: 'hidden'});
}


// === Event Info Access ===

var KEYS = { BACKSPACE: 8,
             TAB:       9,
             RETURN:   13,
             ESC:      27,
             SPACE:    32,
             LEFT:     37,
             UP:       38,
             RIGHT:    39,
             DOWN:     40,
             DELETE:   46 };

var KeyCodes = {
  Up : 63232,
  Down: 63233,
  Left : 63234,
  Right : 63235
};



// === Dropdown Menus ===

/* functionality for an optional drop down menu (example: drop downs in the
nav.) It consists of a link, an arrow, and a menu which appears when the
arrow is clicked. Pass this function an arrow, link, and menu element

arrow_class and arrow_old_class and offset_el is optional
*/
function optional_drop_down_menu(arrow, link, menu, event, arrow_class, arrow_old_class, on_click_callback, off_click_callback, offset_el, offset_info)
{
  if (menu.style.display=='none') {
    menu.style.display='block';
    // if we need to move this menu for z-index reasons, do so.
    if (offset_el && offset_info) {
      for (prop in offset_info) {
        switch(prop) {
          case 'top':
            menu.style.top = (offset_el.offsetTop
                              + offset_info[prop])
                           + 'px';
            break;
          case 'left':
            menu.style.left = (offset_el.offsetLeft
                               + offset_info[prop])
                            + 'px';
            break;
          case 'right':
            menu.style.left = (offset_el.offsetLeft
                               + offset_el.offsetWidth
                               - offset_info[prop]
                               - menu.offsetWidth)
                            +'px';
            break;
          case 'bottom':
            menu.style.top = (offset_el.offsetTop
                              + offset_el.offsetHeight
                              - offset_info[prop]
                              - menu.offsetHeight)
                           + 'px';
            break;
        }
      }
    }

    if (arrow) {
      var old_arrow_classname = arrow_old_class ? arrow_old_class : arrow.className;
    }

    // Lock In Button Pressed State
    if (link) {
      link.className = 'active';
    }

    if (arrow) {
      arrow.className = arrow_class ? arrow_class : 'global_menu_arrow_active';
    }

    var justChanged = true;

    // prevent selects from showing through menu in ie6
    var shim = ge(menu.id + '_iframe');
    if (shim) {
      shim.style.top = menu.style.top;
      shim.style.right = menu.style.right;
      shim.style.display = 'block';
      shim.style.width = (menu.offsetWidth +2) + 'px';
      shim.style.height = (menu.offsetHeight +2) + 'px';
    }

    menu.offclick = function(e) {
      if (!justChanged) {
        // Hide dropdown
        hide(this);

        // Restore Normal link and hover class
        if (link) {
          link.className = '';
        }
        if (arrow) {
          arrow.className = old_arrow_classname;
        }

        var shim = ge(menu.id + '_iframe');
        if (shim) {
          shim.style.display = 'none';
          shim.style.width = menu.offsetWidth + 'px';
          shim.style.height = menu.offsetHeight + 'px';
        }
        if (off_click_callback) { off_click_callback(e); }
        removeEventBase(document, 'click', this.offclick, menu.id);
      } else {
        justChanged = false;
      }
    }.bind(menu);
    if (on_click_callback) { on_click_callback(); }
    addEventBase(document, 'click', menu.offclick, menu.id);
    onunloadRegister(menu.offclick, true);
  }
  return false;
}


/* special case for the app_switcher mneu, we need to set its position since it's right-aligned */
function position_app_switcher() {
  var switcher = ge('app_switcher');
  var menu = ge('app_switcher_menu');
  menu.style.top = (switcher.offsetHeight - 1) + 'px';
  menu.style.right = '0px';
}



// EXPERIMENTAL: generic tooltip class
function hover_tooltip(object, hover_link, hover_class, offsetX, offsetY) {

  if (object.tooltip) {
    var tooltip = object.previousSibling;
    tooltip.style.display = 'block';
    return;
  } else {

    object.parentNode.style.position = "relative";
    var tooltip = document.createElement('div');
    tooltip.className = "tooltip_pro " + hover_class;
    tooltip.style.left=-9999 + 'px';
    tooltip.style.display = 'block';
    tooltip.innerHTML = '<div class="tooltip_text"><span>' + hover_link + '</span></div>' +
      '<div class="tooltip_pointer"></div>';

    object.parentNode.insertBefore(tooltip, object);

    while (tooltip.firstChild.firstChild.offsetWidth <= 0) {
      1;
    }

    var TOOLTIP_PADDING = 16;
    var offsetWidth = tooltip.firstChild.firstChild.offsetWidth + TOOLTIP_PADDING;

    tooltip.style.width = offsetWidth + 'px'; //We need to set the width because of stupid IE

    tooltip.style.display = 'none';

    // calculate where it should go before we make it visible so there's no jerky motion
    tooltip.style.left = offsetX + object.offsetLeft - ((offsetWidth -6 - object.offsetWidth) / 2) + 'px';
    tooltip.style.top = offsetY + 'px';
    tooltip.style.display = 'block';

    object.tooltip = true;

    object.onmouseout = function(e) { hover_clear_tooltip(object) };
  }
}


function hover_clear_tooltip(object) {
  var tooltip = object.previousSibling;
  tooltip.style.display = 'none';
}

function goURI(href) {
  window.location.href = href;
}


function getTableRowShownDisplayProperty() {
  if (ua.ie()) {
    return  'inline';
  } else {
    return 'table-row';
  }
}

function showTableRow()
{
  for ( var i = 0; i < arguments.length; i++ ) {
    var element = ge(arguments[i]);
    if (element && element.style) element.style.display =
        getTableRowShownDisplayProperty();
  }
  return false;
}

function getParentRow(el) {
    el = ge(el);
    while (el.tagName && el.tagName != "TR") {
        el = el.parentNode;
    }
    return el;
}

function show_standard_status(status) {
  s = ge('standard_status');
  if (s) {
    var header = s.firstChild;
    header.innerHTML = status;
    show('standard_status');
  }
}

function hide_standard_status() {
  s = ge('standard_status');
  if (s) {
    hide('standard_status');
  }
}

function adjustImage(obj, stop_word, max) {
  var block = obj.parentNode;
  while (get_style(block, 'display') != 'block' && block.parentNode) {
    block = block.parentNode;
  }

  var width = block.offsetWidth;
  if (obj.offsetWidth > width) {
    try {
      // Internet Explorer's image scaling (as of IE7) looks like poo poo. So what we do to make these look better is pull out the <img />
      // and instead use a <div /> with progid:DXImageTransform, which looks a lot smoother.
      if (ua.ie()) {
          var img_div = document.createElement('div');
          img_div.style.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(src="' + obj.src.replace('"', '%22') + '", sizingMethod="scale")';
          img_div.style.width = width + 'px';
          img_div.style.height = Math.floor(((width / obj.offsetWidth) * obj.offsetHeight))+'px';
          if (obj.parentNode.tagName == 'A') {
            img_div.style.cursor = 'pointer';
          }
          obj.parentNode.insertBefore(img_div, obj);
          obj.parentNode.removeChild(obj);
      } else {
        throw 1;
      }
    } catch (e) {
      obj.style.width = width + 'px';
    }
  }
  remove_css_class_name(obj, 'img_loading');
}

function imageConstrainSize(src, maxX, maxY, placeholderid) {
  var image = new Image();
  image.onload = function() {
    if (image.width > 0 && image.height > 0) {
      var width = image.width;
      var height = image.height;
      if (width > maxX || height > maxY) {
        var desired_ratio = maxY/maxX;
        var actual_ratio = height/width;
        if (actual_ratio > desired_ratio) {
          width = width * (maxY / height);
          height = maxY;
        } else {
          height = height * (maxX / width);
          width = maxX;
        }
      }
      var placeholder = ge(placeholderid);
      var newimage = document.createElement('img');
      newimage.src = src;
      newimage.width = width;
      newimage.height = height;
      placeholder.parentNode.insertBefore(newimage, placeholder);
      placeholder.parentNode.removeChild(placeholder);
    }
  }
  image.src = src;
}

function login_form_change() {
  var persistent = ge('persistent');
  if (persistent) {
    persistent.checked = false;
  }
}

// Note: this is SAFE to call from non-secure pages because it uses fun img\ssl hackery
function require_password_confirmation(onsuccess, oncancel) {
  if ((!getCookie('sid') || getCookie('sid') == '0') || getCookie('pk')) {
    onsuccess();
    return;
  }
  require_password_confirmation.onsuccess = onsuccess;
  require_password_confirmation.oncancel = oncancel;
  (new pop_dialog()).show_ajax_dialog('/ajax/password_check_dialog.php');
}

function search_validate(search_input_id) {
  var search_input = $(search_input_id);

  if (search_input.value != "" &&
      search_input.value != search_input.getAttribute('placeholder')) {
    return true;
  } else {
    //  TODO: Provide a dropdown suggestion that reads
    //  "Please enter a search term" or something to that effect;
    //  for now, we'll just focus the search field, ala Google
    search_input.focus();
    return false;
  }
}

function abTest(data, inline)
{
  AsyncRequest.pingURI('/ajax/abtest.php', {data: data, "post_form_id": null}, true);
  if (!inline) {
    return true;
  }
}

function ac(metadata)
{
  AsyncRequest.pingURI('/ajax/ac.php', {'meta':metadata}, true);
  return true;
}


function alc(metadata)
{
  AsyncRequest.pingURI('/ajax/alc.php', {'meta':metadata}, true);
  return true;
}

function scribe_log(category, message) {
  AsyncRequest.pingURI('/ajax/scribe_log.php', {'category':category, 'message':message, 'post_form_id': null}, true);
}

function play_sound(path, loop) {
  loop = loop||false;

  var s = ge('sound');
  if (!s) {
    s = document.createElement('span');
    s.setAttribute('id', 'sound');
    document.body.appendChild(s);
  }
  s.innerHTML = '<embed src="'+path+'" autostart="true" hidden="true" '+
                'loop="'+(loop?"true":"false")+'" />';
}

// Returns true if an img object has loaded
function image_has_loaded(obj) {

  try {
    if (
      (obj.mimeType!=null && obj.complete && obj.mimeType!='') ||       // ie && safari 3
      (obj.naturalHeight!=null && obj.complete && obj.naturalHeight!=0) // ff
     ) {
      return true;
    } else if (ua.safari() < 3) {
      // workaround for safari 2... complete property only shows up when images are created through JS
      var new_image = new Image();
      new_image.src = obj.src;
      if (new_image.complete == true) {
        return true;
      }
      delete new_image;
    }

  } catch (exception) {

    //  IE7 is throwing an "unspecified error" when you try to look at
    //  properties of `obj' and this fixes it and alert() changes the behavior
    //  and I don't know why it's so upset at the image and this is "unbreak
    //  now!" so this is the high level of quality you get out of me. See
    //  Trac #6956.

    return true;
  }

}

// returns true if an img object has failed to load
function image_has_failed(obj) {
  if (
  (obj.complete==null && obj.width==20 && obj.height==20) ||        // safari - failed images are 20x20
  (obj.mimeType!=null && obj.complete && obj.mimeType=='') ||       // ie - failed images have no mime type
  (obj.naturalHeight!=null && obj.complete && obj.naturalHeight==0) // firefox - failed images have 0 naturalheight
 ) {                                                                               // opera - falls into one of these categories and simply works
   return true;
 }
}

function cavalry_log(cohort, server_time) {

  if (!window.Env) {
    return;
  }

  window.scrollBy(0,1);

  var t = [
    server_time,
    ___tcss,
    ___tjs + ___tcss,
    ___thtml + ___tcss + ___tjs,
    parseInt(Env.t_domcontent - Env.start, 10),
    parseInt(Env.t_onload - Env.start, 10),
    parseInt(Env.t_layout - Env.start, 10),
    parseInt(((new Date()).getTime()) - Env.start, 10),
    parseInt(Env.t_doneonloadhooks - Env.t_willonloadhooks, 10)
  ];

  (new Image()).src = "/common/instrument_endpoint.php?"
    + "g="+cohort
    +"&uri="+encodeURIComponent(window.location)
    +"&t="+t.join(',')
    +"&"+parseInt(Math.random()*10000, 10);
}

/**
 * When the user clicks on the name/picture of someone who they can only see
 * the search profile of, bring it up in a dialog box, rather than sending
 * them to s.php.
 */
function show_search_profile(user_id) {
  var async = new AsyncRequest()
    .setURI('/ajax/search_profile.php')
    .setData({id: user_id})
    .setMethod('GET')
    .setReadOnly(true);
  new Dialog()
    .setAsync(async)
    .setButtons(Dialog.CLOSE)
    .setContentWidth(490)
    .show();
}
function _search_profile_link_handler(link) {
  // Look for links that were generated by the get_search_profile_url PHP
  // function, e.g. facebook.com/s.php?k=100000080&id=500011067, and make
  // it so if the user clicks one, we show them the equivalent content
  // in a dialog box instead.
  var uri = new URI(link.href);
  if (uri.getPath() == '/s.php') {
    var query = uri.getQueryData();
    if (query.k == 100000080 /* KEY_USERID */) {
      show_search_profile(query.id);
      return false;
    }
  }
}
onloadRegister(function() {
  LinkController.registerHandler(_search_profile_link_handler);
});

/**
 * Makes it so that, if the user edits the given form, and then tries to
 * navigate away from the page without submitting the form, s/he will first
 * get prompted with a dialog box to confirm leaving.
 *
 * See render_start_form_with_unsaved_warning.
 */
function warn_if_unsaved(form_id) {
  var form = ge(form_id);

  if (!form) {
    Util.error("warn_if_unsaved couldn't find form in order to save its "
             + "original state.  This is probably because you called "
             + "render_start_form_with_unsaved_warning to render a form, "
             + "but then didn't echo it into page.  To get around this, you "
             + "can call render_start_form, and then call warn_if_unsaved "
             + "yourself once you've caused the form to appear.");
    return;
  }

  if (!_unsaved_forms_to_check_for) {
    // Means it's the first time we're calling warn_if_unsaved.
    _unsaved_forms_to_check_for = {};
    LinkController.registerHandler(_check_for_unsaved_forms);
  }

  form.original_state = serialize_form(form);
  _unsaved_forms_to_check_for[form_id] = true;
}
function _check_for_unsaved_forms(link) {
  for (var form_id in _unsaved_forms_to_check_for) {
    var form = ge(form_id);
    if (form && form.original_state &&
        !are_equal(form.original_state, serialize_form(form))) {
      var href = link.href;
      // TODO: someday this will have to play more nicely
      // with Quickling / other onclick handlers.

      var submit = _find_first_submit_button(form);
      var buttons = [];
      if (submit) {
        buttons.push({ name: 'save', label: tx('sh:save-button'),
                       handler: bind(submit, 'click') });
      }
      buttons.push({ name: 'dont_save', label: tx('uw:dont-save'),
                     handler: function() { window.location.href = href; } });
      buttons.push(Dialog.CANCEL);

      new Dialog()
        .setTitle(tx('uw:title'))
        .setBody(tx('uw:body'))
        .setButtons(buttons)
        .setModal()
        .show();
      return false;
    }
  }
}
function _find_first_submit_button(root_element) {
  var inputs = root_element.getElementsByTagName('input');
  for (var i = 0; i < inputs.length; ++i) {
    if (inputs[i].type.toUpperCase() == 'SUBMIT') {
      return inputs[i];
    }
  }
  return null;
}
_unsaved_forms_to_check_for = undefined;


/* -( Bootstrap )------------------------------------------------------------ */

  //  This section contains code which runs implicitly when this file is
  //  included. Please put implicitly-running non-definition code here so we can
  //  keep track of what's going on.

        ua.populate();
        _bootstrapEventHandlers();
        adjustUABehaviors();

        // Lower the page domain.  This allows our iframes to communicate with
        // their parent window even if they were served by another subdomain.
        // If you write an iframe that needs to use "window.parent", make sure
        // you either include base.js, or run this line manually.
        // Also, NEVER use navigator.userAgent in your own code. The reason it is
        // used here instead of the ua object is for consistency on pages that
        // don't use base.js.
        if (navigator && navigator.userAgent && !(parseInt((/Gecko\/([0-9]+)/.exec(navigator.userAgent) || []).pop()) <= 20060508)) {
          //document.domain = 'facebook.com';
        }


/* -( End )------------------------------------------------------------------ */



  /**************  lib/dom/dom.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *
 *  @author   epriestley, marcel
 *
 *  @requires function-extensions array-extensions list ua dom-misc util
 *            dom-html
 *  @provides dom dom-core
 */

var DOM = {

  tryElement : function(id) {
    if (typeof(id) == 'undefined') {
      Util.error('Tried to get "undefined" element!');
      return null;
    }

    var obj;
    if (typeof(id) == 'string') {
      obj = document.getElementById(id);

      if (!(ua.ie() >= 7)) {
        return obj;
      }

      // Workaround for a horrible bug in IE7
      // http://remysharp.com/2007/02/10/ie-7-breaks-getelementbyid/
      if (!obj) {
        return null;
      // In the case where `obj' is a form element with an input that has
      // the name `id', obj.id is an input instead of the actual id.
      } else if (typeof(obj.id) == 'string' && obj.id == id) {
        return obj;
      } else {

        var candidates = document.getElementsByName(id);
        if (!candidates || !candidates.length) {
          return null;
        }

        var maybe = [];
        for (var ii = 0; ii < candidates.length; ii++) {
          var c = candidates[ii];

          //  If we have no `id', this can't possibly be the real element; skip
          //  it -- unless "id" is "0" or empty or something?
          if (!c.id && id) {
            continue;
          }

          //  If we have an `id' and it's a string but it's wrong, skip it.
          if (typeof(c.id) == 'string' && c.id != id) {
            continue;
          }

          //  We're left with forms with the correct ID that is obscured by an
          //  input named `id' and maybe some edge cases where multiple elements
          //  have the same ID.

          maybe.push(candidates[ii]);
        }

        if (!maybe.length) {
          return null;
        }

        return maybe[0];
      }
    }

    return id;
  },

  getElement : function(id) {
    var el = DOM.tryElement.apply(null, arguments);
    if (!el) {
      Util.warn(
        'Tried to get element %q, but it is not present in the page. (Use '    +
        'ge() to test for the presence of an element.)',
        arguments[0]);
    }
    return el;
  },

  setText : function(el, text) {
    if (ua.firefox()) {
      el.textContent = text;
    } else {
      el.innerText = text;
    }
  },

  getText : function(el) {
    if (ua.firefox()) {
      return el.textContent;
    } else {
      return el.innerText;
    }
  },

  setContent : function(el, content) {

    //  This is a horrible browser-specific discography hack. I have no idea
    //  what is going on here.

    if (ua.ie()) {
      for (var ii = el.childNodes.length - 1; ii >= 0; --ii) {
        DOM.remove(el.childNodes[ii]);
      }
    } else {
      el.innerHTML = '';
    }

    if (content instanceof HTML) {
      set_inner_html(el, content.toString());
    } else if (is_scalar(content)) {
      content = document.createTextNode(content);
      el.appendChild(content);
    } else if (is_node(content)) {
      el.appendChild(content);
    } else if (content instanceof Array) {
      for (var ii = 0; ii < content.length; ii++) {
        var node = content[ii];
        if (!is_node(node)) {
          node = document.createTextNode(node);
        }
        el.appendChild(node);
      }
    } else {
      Util.error(
        'No way to set content %q.', content);
    }
  },

  remove : function(element) {
    element = $(element);
    if (element.removeNode) {
      element.removeNode(true);
    } else {
      for (var ii = element.childNodes.length-1; ii >=0; --ii) {
        DOM.remove(element.childNodes[ii]);
      }
      element.parentNode.removeChild(element);
    }
  },

  create : function(element, attributes, children) {
    element = document.createElement(element);

    if (attributes) {
      attributes = copy_properties({}, attributes);
      if (attributes.style) {
        copy_properties(element.style, attributes.style);
        delete attributes.style;
      }
      copy_properties(element, attributes);
    }

    if (children != undefined) {
      DOM.setContent(element, children);
    }

    return element;
  },

  scry : function(element, pattern) {
    pattern = pattern.split('.');
    var tag = pattern[0] || null;
    if (!tag) {
      return [];
    }
    var cls = pattern[1] || null;

    var candidates = element.getElementsByTagName(tag);
    if (cls !== null) {
      var satisfy = [];
      for (var ii = 0; ii < candidates.length; ii++) {
        if (CSS.hasClass(candidates[ii], cls)) {
          satisfy.push(candidates[ii]);
        }
      }
      candidates = satisfy;
    }

    return candidates;
  },

  prependChild : function(parent, child) {
    parent = $(parent);
    if (parent.firstChild) {
      parent.insertBefore(child, parent.firstChild);
    } else {
      parent.appendChild(child);
    }
  },

  getCaretPosition : function(element) {
    element = $(element);

    if (!is_node(element, ['input', 'textarea'])) {
      return {start: undefined, end: undefined};
    }

    if (!document.selection) {
      return {start: element.selectionStart, end: element.selectionEnd};
    }

    if (is_node(element, 'input')) {
      var range = document.selection.createRange();
      return {start: -range.moveStart('character', -element.value.length),
                end: -range.moveEnd('character', -element.value.length)};
    } else {
      var range = document.selection.createRange();
      var range2 = range.duplicate();
      range2.moveToElementText(element);
      range2.setEndPoint('StartToEnd', range);
      var end = element.value.length - range2.text.length;
      range2.setEndPoint('StartToStart', range);
      return {start: element.value.length - range2.text.length, end: end};
    }
  },

  addEvent : function(element, type, func, name_hash) {
    return addEventBase(element, type, func, name_hash);
  }

};

var $N = DOM.create;
var ge = DOM.tryElement;

var $$ = function _$$(rules) {
  //  Avoid calling bind() at interpretation time because of concurrency issues
  //  with Bootloader.
  var args = [document].concat(Array.prototype.slice.apply(arguments));
  return DOM.scry.apply(null, args);
}

var  $ = DOM.getElement;

var remove_node         = DOM.remove;
var prependChild        = DOM.prependChild;
var get_caret_position  = DOM.getCaretPosition;



function is_node(o, of_type) {

  if (typeof(Node) == 'undefined') {
    Node = null;
  }

  try {
    if (!o || !((Node != undefined && o instanceof Node) || o.nodeName)) {
      return false;
    }
  } catch(ignored) {
    return false;
  }

  if (typeof(of_type) !== "undefined") {

    if (!(of_type instanceof Array)) {
      of_type = [of_type];
    }

    var name;
    try {
      name = new String(o.nodeName).toUpperCase();
    } catch (ignored) {
      return false;
    }

    for (var ii = 0; ii < of_type.length; ii++) {
      try {
        if (name == of_type[ii].toUpperCase()) {
          return true;
        }
      } catch (ignored) {
      }
    }

    return false;
  }

  return true;
}


/* determines whether or not a base_obj is a descendent of the target_id obj */
function is_descendent(base_obj, target_id) {
  var target_obj = ge(target_id);
  if (base_obj == null) return;
  while (base_obj != target_obj) {
    if (base_obj.parentNode) {
      base_obj = base_obj.parentNode;
    } else {
      return false;
    }
  }
  return true;
}


// From Corinis; available in the public domain per author
function iterTraverseDom(root, visitCb) {
  var c = root, n = null;
  var it = 0;
  do {
    n = c.firstChild;
    if (!n) {
      if (visitCb(c) == false)
        return;
      n = c.nextSibling;
    }

    if (!n) {
      var tmp = c;
      do {
        n = tmp.parentNode;
        if (n == root)
          break;

        if (visitCb(n) == false)
          return;

        tmp = n;
        n = n.nextSibling;
      }
      while (!n);
    }

    c = n;
  }
  while (c != root);
}



function insertAfter(parent, child, elem) {
  if (parent != child.parentNode) {
    Util.error('child is not really a child of parent - wtf, seriously.');
  }
  if (child.nextSibling) {
    var ret = parent.insertBefore(elem, child.nextSibling);
  } else {
    var ret = parent.appendChild(elem);
  }
  if (!ret) {
    return null;
  }
  return elem;
}


// sets the caret position of a textarea or input. end is optional and will default to start
function set_caret_position(obj, start, end) {
  if (document.selection) {
    // IE is inconsistent about character offsets when it comes to carriage returns, so we need to manually take them into account
    if (obj.tagName == 'TEXTAREA') {
      var i = obj.value.indexOf("\r", 0);
      while (i != -1 && i < end) {
        end--;
        if (i < start) {
          start--;
        }
        i = obj.value.indexOf("\r", i + 1);
      }
    }
    var range = obj.createTextRange();
    range.collapse(true);
    range.moveStart('character', start);
    if (end != undefined) {
      range.moveEnd('character', end - start);
    }
    range.select();
  } else {
    obj.selectionStart = start;
    var sel_end = end == undefined ? start : end;
    obj.selectionEnd = Math.min(sel_end, obj.value.length);
    obj.focus();
  }
}


  /**************  lib/dom/css.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @author epriestley, marcel
 *
 *  @requires string dom util ua
 *  @provides css
 */


var CSS = {

  hasClass : function(element, className) {
    if (element && className && element.className) {
      return new RegExp('\\b'+trim(className)+'\\b').test(element.className);
    }
    return false;
  },

  addClass : function(element, className) {
    if (element && className) {
      if (!CSS.hasClass(element, className)) {
        if (element.className) {
          element.className += ' ' + trim(className);
        } else {
          element.className  = trim(className);
        }
      }
    }

    return this;
  },

  removeClass : function(element, className) {
    if (element && className && element.className) {
      className = trim(className);

      var regexp = new RegExp('\\b'+className+'\\b', 'g');
      element.className = element.className.replace(regexp, '');
    }

    return this;
  },

  conditionClass : function(element, className, shouldShow) {
    if (shouldShow) {
      CSS.addClass(element, className);
    } else {
      CSS.removeClass(element, className);
    }
  },

  setClass : function(element, className) {
    element.className = className;

    return this;
  },

  toggleClass : function(element, className) {
    if (CSS.hasClass(element, className)) {
      return CSS.removeClass(element, className);
    } else {
      return CSS.addClass(element, className);
    }
  },

  /**
   * Return a style element for the specified object.  Will
   * return the computed style element if available, otherwise
   * returns the in-line style definition.
   *
   * IMPORTANT: THERE ARE VERY FEW VALID USE CASES FOR THIS FUNCTION! Only use this function if you are
   *            100% sure that you need to. And even then be sure to ask Evan or Marcel about it first.
   */
  getStyle : function(element, property) {
    element = $(element);

    function hyphenate(property) {
      // Convert to hyphenated property
      return property.replace(/[A-Z]/g, function(match) {
        return '-' + match.toLowerCase();
      });
    }

    // Preferred W3C method
    if (window.getComputedStyle) {
      return window.getComputedStyle(element, null).getPropertyValue(hyphenate(property));
    }

    // Safari
    if (document.defaultView && document.defaultView.getComputedStyle) {
      var computedStyle = document.defaultView.getComputedStyle(element, null);
      // Safari returns null from computed style if the display of the element is none.
      // This is a bug in Safari. If object's display is none here, we just return
      // "none" if the user is asking for the "display" property, or we error otherwise.
      // It's probably possible to implement this correctly, but there are many details
      // you need to get right. See http://dev.mootools.net/ticket/51
      if (computedStyle)
        return computedStyle.getPropertyValue(hyphenate(property));
      if (property == "display")
        return "none";
      Util.error("Can't retrieve requested style %q due to a bug in Safari", property);
    }

    // IE and derivatives
    if (element.currentStyle) {
      return element.currentStyle[property];
    }

    // Crappy in-line only lookup
    return element.style[property];
  },

  setOpacity : function(element, opacity) {
    var opaque = (opacity == 1);

    try {
      element.style.opacity = (opaque ? '' : ''+opacity);
    } catch (ignored) {}

    try {
      element.style.filter  = (opaque ? '' : 'alpha(opacity='+(opacity*100)+')');
    } catch (ignored) {}
  },

  getOpacity : function(element) {
    var opacity = get_style(element, 'filter');
    var val = null;
    if (opacity && (val = /(\d+(?:\.\d+)?)/.exec(opacity))) {
      return parseFloat(val.pop()) / 100;
    } else if (opacity = get_style(element, 'opacity')) {
      return parseFloat(opacity);
    } else {
      return 1.0;
    }
  },

  Cursor : {

    kGrabbable : 'grabbable',
    kGrabbing  : 'grabbing',
    kEditable  : 'editable',

    set : function(element, name) {

      element = element || document.body;

      switch (name) {
        case CSS.Cursor.kEditable:
          name = 'text';
          break;
        case CSS.Cursor.kGrabbable:
          if (ua.firefox()) {
            name = '-moz-grab';
          } else {
            name = 'move';
          }
          break;
        case CSS.Cursor.kGrabbing:
          if (ua.firefox()) {
            name = '-moz-grabbing';
          } else {
            name = 'move';
          }
          break;
      }

      element.style.cursor = name;
    }
  }
};

var has_css_class_name    = CSS.hasClass;
var add_css_class_name    = CSS.addClass;
var remove_css_class_name = CSS.removeClass;
var toggle_css_class_name = CSS.toggleClass;
var get_style             = CSS.getStyle;
var set_opacity           = CSS.setOpacity;
var get_opacity           = CSS.getOpacity;




  /**************  lib/dom/form.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides dom-form
 */

function getRadioFormValue(obj) {
  for (i = 0; i < obj.length; i++) {
   if (obj[i].checked) {
     return obj[i].value;
   }
  }
  return null;
}

// === Forms ===

// http://www.quirksmode.org/dom/getElementsByTagNames.html
function getElementsByTagNames(list,obj) {
  if (!obj) var obj = document;
  var tagNames = list.split(',');
  var resultArray = new Array();
  for (var i=0;i<tagNames.length;i++) {
    var tags = obj.getElementsByTagName(tagNames[i]);
    for (var j=0;j<tags.length;j++) {
      resultArray.push(tags[j]);
    }
  }
  var testNode = resultArray[0];
  if (!testNode) return [];
  if (testNode.sourceIndex) {
    resultArray.sort(function (a,b) {
      return a.sourceIndex - b.sourceIndex;
    });
  }
  else if (testNode.compareDocumentPosition) {
    resultArray.sort(function (a,b) {
      return 3 - (a.compareDocumentPosition(b) & 6);
    });
  }
  return resultArray;
}

function get_all_form_inputs(root_element) {
  if (!root_element) {
    root_element = document;
  }
  return getElementsByTagNames('input,select,textarea,button', root_element);
}

function get_form_select_value(select) {
  return select.options[select.selectedIndex].value;
}

function set_form_select_value(select, value) {
  for (var i = 0; i < select.options.length; ++i) {
    if (select.options[i].value == value) {
      select.selectedIndex = i;
      break;
    }
  }
}

// if you want to find an attribute of a <form> node, doing form.attr_name or
// in IE6 even form.getAttribute('attr_name') won't work in the event that the
// form has an input node named "attr_name".  so use this function instead.
// (see http://bugs.developers.facebook.com/show_bug.cgi?id=251 )
function get_form_attr(form, attr) {
  var val = form[attr];
  if (typeof val == 'object' && val.tagName == 'INPUT') {
    var pn = val.parentNode, ns = val.nextSibling, node = val;
    pn.removeChild(node);
    val = form[attr];
    ns ? pn.insertBefore(node, ns) : pn.appendChild(node);
  }
  return val;
}

function serialize_form_helper(data, name, value) {
  var match = /([^\]]+)\[([^\]]*)\](.*)/.exec(name);
  if (match) {
    data[match[1]] = data[match[1]] || {};
    if (match[2] == '') {
      var i = 0;
      while (data[match[1]][i] != undefined) {
        i++;
      }
    } else {
      i = match[2];
    }
    if (match[3] == '') {
      data[match[1]][i] = value;
    } else {
      serialize_form_helper(data[match[1]], i.concat(match[3]), value);
    }
  } else {
    data[name] = value;
  }
}

// turns stuff like {0: 'foo', 1: 'bar'} into ['foo', 'bar']
function serialize_form_fix(data) {
  var keys = [];
  for (var i in data) {
    if (data instanceof Object) {
      data[i] = serialize_form_fix(data[i]);
    }
    keys.push(i);
  }
  var j = 0, is_array = true;
  keys.sort().each(function(i) {
    if (i != j++) {
      is_array = false;
    }
  });
  if (is_array) {
    var ret = {};
    keys.each(function(i) {
      ret[i] = data[i];
    });
    return ret;
  } else {
    return data;
  }
}

function serialize_form(obj) {
  var data = {};
  var elements = obj.tagName == 'FORM' ? obj.elements : get_all_form_inputs(obj);
  for (var i = elements.length - 1; i >= 0; i--) {
    if (elements[i].name && !elements[i].disabled) {
      // Serialize If
      // 1) unrecognizable type
      // 2) radio buttons or checkboxes that are checked
      // 3) type is in (text,password,hidden)
      // 4) tag is in (textarea,select)
      if (!elements[i].type ||
          ((elements[i].type == 'radio' || elements[i].type == 'checkbox') &&
            elements[i].checked) ||
          elements[i].type == 'text' ||
          elements[i].type == 'password' ||
          elements[i].type == 'hidden' ||
          elements[i].tagName == 'TEXTAREA' ||
          elements[i].tagName == 'SELECT') {
        serialize_form_helper(data, elements[i].name, elements[i].value);
      }
    }
  }
  return serialize_form_fix(data);
}

function is_button(element) {
  var tagName = element.tagName.toUpperCase();
  if (tagName == 'BUTTON') {
    return true;
  }
  if (tagName == 'INPUT' && element.type) {
    var type = element.type.toUpperCase();
    return type == 'BUTTON' || type == 'SUBMIT';
  }
  return false;
}





// This little guy takes a get style request except it does it as a POST
function do_post(url) {
  var pieces=/(^([^?])+)\??(.*)$/.exec(url);
  var form=document.createElement('form');
  form.action=pieces[1];
  form.method='post';
  form.style.display='none';
  var sparam=/([\w]+)(?:=([^&]+)|&|$)/g;
  var param=null;
  if (ge('post_form_id'))
    pieces[3]+='&post_form_id='+$('post_form_id').value;
  while (param=sparam.exec(pieces[3])) {
    var input=document.createElement('input');
    input.type='hidden';
    input.name=decodeURIComponent(param[1]);
    input.value=decodeURIComponent(param[2]);
    form.appendChild(input);
  }
  document.body.appendChild(form);
  form.submit();
  return false;
}

// This does a POST of the variables in params
function dynamic_post(url, params) {
  var form=document.createElement('form');
  form.action=url;
  form.method='POST';
  form.style.display='none';
  if (ge('post_form_id')) {
    params['post_form_id'] = $('post_form_id').value;
  }
  for (var param in params) {
    var input=document.createElement('input');
    input.type='hidden';
    input.name=param;
    input.value=params[param];
    form.appendChild(input);
  }
  document.body.appendChild(form);
  form.submit();
  return false;
}



  /**************  lib/dom/html.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides dom-html
 *  @requires function-extensions
 */

/**
 *  This explicitly marks a string as an HTML string, for use by DOM.* methods.
 *  Usage:
 *
 *    DOM.setContent(element, HTML('<big>!</big>'));
 *
 *  @author epriestley
 */
function /* class */ HTML(content) {
  if (this === window) {
    return new HTML(content);
  }
  this.content = content;
  return this;
}

copy_properties(HTML.prototype, {
  toString : function() {
    return this.content;
  }
});


  /**************  lib/dom/misc.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides dom-misc
 *  @requires dom ua-adjust
 */
function show() {
  for (var i = 0; i < arguments.length; i++) {
    var element = ge(arguments[i]);
    if (element && element.style) element.style.display = '';
  }
  return false;
}

function hide() {
  for (var i = 0; i < arguments.length; i++) {
    var element = ge(arguments[i]);
    if (element && element.style) element.style.display = 'none';
  }
  return false;
}

function shown(el) {
    el = ge(el);
    return (el.style.display != 'none' && !(el.style.display=='' && el.offsetWidth==0));
}

function toggle() {
  for (var i = 0; i < arguments.length; i++) {
    var element = $(arguments[i]);
    element.style.display = get_style(element, "display") == 'block' ? 'none' : 'block';
  }
  return false;
}

/**
 * Sets innerHTML and executes JS that may be embedded.
 *
 * @param defer_js_execution  Wait until after this thread is done executing
 *                            to execute the JS.  This is a good idea if
 *                            you're setting a large amount of HTML, and want
 *                            to make the browser render the HTML before
 *                            starting on potentially-expensive JS evaluation.
 */
function set_inner_html(obj, html, defer_js_execution /* = false */) {

  // fix ridiculous IE bug: without some text before these tags, they get
  // stripped out when we set the innerHTML in a dialogpro.
  var dummy = '<span style="display:none">&nbsp</span>';
  html = html.replace('<style', dummy+'<style');
  html = html.replace('<STYLE', dummy+'<STYLE');
  html = html.replace('<script', dummy+'<script');
  html = html.replace('<SCRIPT', dummy+'<SCRIPT');

  obj.innerHTML = html;

  if (defer_js_execution) {
    eval_inner_js.bind(null, obj).defer();
  } else {
    eval_inner_js(obj);
  }

  addSafariLabelSupport(obj);
  (function() {
    LinkController.bindLinks(obj);
  }).defer();
}

// Executes JS that may be embedded in an element
function eval_inner_js(obj) {
  var scripts = obj.getElementsByTagName('script');
  for (var i=0; i<scripts.length; i++) {
    if (scripts[i].src) {
      var script = document.createElement('script');
      script.type = 'text/javascript';
      script.src = scripts[i].src;
      document.body.appendChild(script);
    } else {
      try {
        eval_global(scripts[i].innerHTML);
      } catch (e) {
        if (typeof console != 'undefined') {
          console.error(e);
        }
      }
    }
  }
}

// Evaluates JS in the global scope
// This seems really fragile but it works in Safari, Firefox, IE6, IE7, and even Opera.
// It even blocks properly so alert(1);eval_global('alert(2)');alert(3); will alert in order
function eval_global(js) {
  var obj = document.createElement('script');
  obj.type = 'text/javascript';

  try {
    obj.innerHTML = js;
  } catch(e) {
    obj.text = js;
  }


  document.body.appendChild(obj);
}



  /**************  lib/dom/control.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @requires function-extensions dom
 *  @provides control-dom
 */

function /* class */ DOMControl(root) {
  copy_properties(this, {
        root : root && $(root),
    updating : false
  });


  if (root) {
    root.getControl = identity.bind(null, this);
  }
}

copy_properties(DOMControl.prototype, {
  getRoot : function() {
    return this.root;
  },
  beginUpdate : function() {
    if (this.updating) {
      return false;
    }
    this.updating = true;
    return true;
  },
  endUpdate : function() {
    this.updating = false;
  },
  update : function() {
    if (!this.beginUpdate()) {
      return this;
    }
    this.onupdate();
    this.endUpdate();
  }
});




  /**************  lib/dom/controls/text_input.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @author   epriestley
 *
 *  @requires control-dom function-extensions
 *  @provides control-textinput
 */

function /* class */ TextInputControl(textinput) {
  this.parent.construct(this, textinput);

  copy_properties(this, {
      placeholderText : null,
            maxLength : this.getRoot().maxLength || null,
                radio : null,
              focused : false,
    nativePlaceholder : false
  });

  var r = this.getRoot();

  //  If this is a "Search" input in Safari, there's a native placeholder
  //  implementation available; use that instead of our own.



  if ((String(r.type).toLowerCase() == 'search') && ua.safari()) {
    this.nativePlaceholder = true;
    this.setPlaceholderText(r.getAttribute('placeholder'));
  }

  DOM.addEvent(r, 'focus',    this.setFocused.bind(this, true));
  DOM.addEvent(r, 'blur',     this.setFocused.bind(this, false));

  var up = this.update.bind(this);


  DOM.addEvent(r, 'keydown',  up);
  DOM.addEvent(r, 'keyup',    up);
  DOM.addEvent(r, 'keypress', up);
  setInterval(up, 150);

  this.setFocused(false);
}

TextInputControl.extend(DOMControl);

copy_properties(TextInputControl.prototype, {

  /**
   *  Associate the attached element with a radio button, which will be
   *  automatically focused when the text input is selected.
   */
  associateWithRadioButton : function(element) {
    this.radio = element && $(element);
    return this;
  },

  setMaxLength : function(maxlength) {
    this.maxLength = maxlength;
    this.getRoot().maxLength = this.maxLength || null;
    return this;
  },


  getValue : function() {
    if (this.getRoot().value == this.placeholderText) {
      return null;
    }
    return this.getRoot().value;
  },


  isEmpty : function() {
    var v = this.getValue();
    return (v === null || v == '');
  },


  setValue : function(value) {
    this.getRoot().value = value;
    this.update();

    return this;
  },


  clear : function() {
    return this.setValue('');
  },


  isFocused : function() {
    return this.focused;
  },

  setFocused : function(focused) {
    this.focused = focused;


    //  Inputs with type "search" handle their own "placeholder" behavior.


    if (this.placeholderText && !this.nativePlaceholder) {
      var r = this.getRoot();
      var v = r.value;
      if (this.focused) {
        CSS.removeClass(r, 'DOMControl_placeholder');
        if (this.isEmpty()) {
          this.clear();
        }
      } else if (this.isEmpty()) {
        CSS.addClass(r, 'DOMControl_placeholder');
        this.setValue(this.placeholderText);
      }
    }


    this.update();

    return this;
  },

  setPlaceholderText : function(text) {
    this.placeholderText = text;

    if (this.nativePlaceholder) {
      this.getRoot().setAttribute('placeholder', text);
    }

    return this.setFocused(this.isFocused());
  },

  /**
   *  Respond to an event.
   */
  onupdate : function() {

    if (this.radio) {
      if (this.focused) {
        this.radio.checked = true;
      }
    }

    //  Note: the default "maxlength" property of inputs without one in Firefox
    //  is "-1", so test for maxLength > 0.
    //
    //    >>> $N('input').maxLength
    //    -1

    var r = this.getRoot();
    if (this.maxLength > 0) {
      if (r.value.length > this.maxLength) {
        r.value = r.value.substring(0, this.maxLength);
      }
    }
  }
});


/* -(  Deprecated Placeholder API  )----------------------------------------- */


function placeholderSetup(id) {
  if (!ge(id)) {
    Util.warn(
      'Setting up a placeholder for an element which does not exist: %q.',
      id);
    return;
  }


  //  Firefox will allow you to access the value of `.placeholder' ONLY by using
  //  getAttribute().

  if (!$(id).getAttribute('placeholder')) {
    Util.warn(
      'Setting up a placeholder for an element with no placeholder text: %q.',
      id);
    return;
  }


  return new TextInputControl($(id))
    .setPlaceholderText($(id).getAttribute('placeholder'));
}


  /**************  lib/dom/controls/text_area.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @author   epriestley
 *
 *  @requires function-extensions control-textinput vector
 *  @provides control-textarea
 */

function /* class */ TextAreaControl(textarea) {

  copy_properties(this, {
          autogrow : false,
            shadow : null,
    originalHeight : null,
      metricsValue : null
  });

  this.parent.construct(this, textarea);
};

TextAreaControl.extend(TextInputControl);

copy_properties(TextAreaControl.prototype, {

  setAutogrow : function(autogrow) {
    this.autogrow = autogrow;
    this.refreshShadow();
    return this;
  },

  onupdate : function() {
    this.parent.onupdate();

    var r = this.getRoot();
    if (this.autogrow && r.value != this.metricsValue) {
      this.metricsValue = r.value;

      copy_properties(this.shadow.style, {
          fontSize : parseInt(CSS.getStyle(r, 'fontSize'), 10) + 'px',
        fontFamily : CSS.getStyle(r, 'fontFamily') + 'px',
             width : (Vector2.getElementDimensions(r).x - 8) + 'px'
      });

      DOM.setContent(this.shadow, HTML(htmlize(r.value)));
      r.style.height = Math.max(
        this.originalHeight,
        Vector2.getElementDimensions(this.shadow).y + 15) + 'px';
    }
  },

  refreshShadow : function() {
    if (this.autogrow) {
      this.shadow = $N('div', {className: 'DOMControl_shadow'});
      document.body.appendChild(this.shadow);
      var r = this.getRoot();
      this.originalHeight = parseInt(CSS.getStyle(r, 'height'))
        || Vector2.getElementDimensions(this.getRoot()).y;
    } else {
      if (this.shadow) {
        DOM.remove(this.shadow);
      }
      this.shadow = null;
    }
  }


});


/* -(  Deprecated Textarea APIs  )------------------------------------------- */


function autogrow_textarea(element) {
  element = $(element);
  if (!element._hascontrol) {
    element._hascontrol = true;
    new TextAreaControl(element).setAutogrow(true);
  }
}

function textarea_maxlength(element, length) {
  element = $(element);
  if (!element._hascontrol) {
    element._hascontrol = true;
    new TextAreaControl(element).setMaxLength(length);
  }
}



  /**************  key_event_controller.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @author   epriestley
 *  @requires vector keycodes
 *  @provides key-event-controller
 */

/**
 *  KeyEventController allows you to capture and respond to keyboard commands.
 *  For example, to play a sound every time the user presses the "m" key:
 *
 *    function quack_key_handler(event, type) {
 *      play_sound('/intern/sound/quack.wav');
 *      return false;
 *    }
 *
 *    KeyEventController.registerKey('m', quack_key_handler);
 *
 *  If your application is consuming the keystroke, you should return `false'
 *  from your handler; this will abort the event and keep it from propagating.
 *
 *  You can also register for most special keys, such as the arrow keys, by
 *  name (e.g. "LEFT", "RIGHT", "RETURN", "ESCAPE", etc.).
 *
 *  By default, your handler will be called only on keydown, only for unmodified
 *  (by control, alt or meta) keypresses, and only if the event target is 
 *  innocuous (for instance, not a textarea). These are the correct filters for
 *  most applications, but if you want to be notified of a broader or narrower
 *  set of events you may provide your own filter function:
 *
 *    function custom_key_filter(event, type) {
 *      if (event.ctrlKey) {
 *        return true;
 *      }
 *      return false;
 *    }
 *
 *    KeyEventController.registerKey('n', quack_key_handler, custom_key_filter);
 *
 *  The filter function should return true to allow the event, and false to
 *  filter it. In this example, the handler will receive keydown, keypress, and
 *  keyup events regardless of event target, provided the control key is
 *  pressed.
 *
 *  Several primitive filters are provided: filterEventTypes,
 *  filterEventTargets, and filterEventModifiers. These filters can be 
 *  selectively chained with custom logic.
 *
 *  For both filter and handler callbacks, the first parameter will be the 
 *  event and the second will be a string indicating its type, one of 
 *  "onkeyup", "onkeydown", or "onkeypress".
 *
 *  @author epriestley
 */
function /* class */ KeyEventController( ) {

  copy_properties(this, {
    handlers: {}
  });

  document.onkeyup    = this.onkeyevent.bind(this, 'onkeyup');
  document.onkeydown  = this.onkeyevent.bind(this, 'onkeydown');
  document.onkeypress = this.onkeyevent.bind(this, 'onkeypress');

}

copy_properties(KeyEventController, {

  instance : null,

  getInstance : function() {
    return KeyEventController.instance ||
          (KeyEventController.instance = new KeyEventController());
  },
  
  defaultFilter : function(event, type) {
    event = event_get(event);
    return KeyEventController.filterEventTypes(event, type)   &&
           KeyEventController.filterEventTargets(event, type) &&
           KeyEventController.filterEventModifiers(event, type);
  },
  
  filterEventTypes : function(event, type) {
    
    if (type === 'onkeydown') {
      return true;
    }
    
    return false;
  },
  
  filterEventTargets : function(event, type) {
    

    var target = event_get_target(event);

    if (target !== document.body            &&  // Safari
        target !== document.documentElement) {  // Firefox
      
      if (!ua.ie()) {
        return false;
      }
      
      if (is_node(target, ['input', 'select', 'textarea', 'object', 'embed'])) {
        return false;
      }
    }
    
    return true;    
  },
  
  filterEventModifiers : function(event, type) {

    if (event.ctrlKey || event.altKey || event.metaKey || event.repeat) {
      return false;
    }

    return true;
  },

  registerKey : function(key, callback, filter_callback) {
    if (filter_callback === undefined) {
      filter_callback = KeyEventController.defaultFilter;
    }
    
    var ctl = KeyEventController.getInstance();
    var eqv = ctl.mapKey(key);

    for (var ii = 0; ii < eqv.length; ii++) {
      key = eqv[ii];
      if (!ctl.handlers[key]) {
        ctl.handlers[key] = [];
      }

      ctl.handlers[key].push({
        callback : callback,
          filter : filter_callback
      });
    }
  },

  bindToAccessKeys : function( ) {
    var ii, k;
    var links = document.getElementsByTagName('a');
    for (ii = 0; ii < links.length; ii++) {
      if (links[ii].accessKey) {
        if (k) {
          KeyEventController.registerKey(
            k,
            bind(KeyEventController, 'accessLink', links[ii]));
        }
      }
    }

    var inputs = document.getElementsByTagName('input');
    for (ii = 0; ii < inputs.length; ii++) {
      if (inputs[ii].accessKey) {
        if (k) {
          KeyEventController.registerKey(
            k,
            bind(KeyEventController, 'accessInput', inputs[ii]));
        }
      }
    }

    var areas  = document.getElementsByTagName('textarea');
    for (ii = 0; ii < areas.length; ii++) {
      if (areas[ii].accessKey) {
        if (k) {
          KeyEventController.registerKey(
            k,
            bind(KeyEventController, 'accessInput', areas[ii]));
        }
      }
    }

  },

  accessLink : function(l, e) {
    if (l.onclick) {
      return l.onclick(e);
    }

    if (l.href) {
      window.location.href = l.href;
    }
  },

  accessInput : function(i, e) {
    Vector2.scrollTo(i);
    i.focus(e);

    if (i.type == 'submit') {
      i.form.submit( );
    }
  },

  keyCodeMap : {
         '[' : [219],
         ']' : [221],
         '`' : [192],
      'LEFT' : [KEYS.LEFT, KeyCodes.Left],
     'RIGHT' : [KEYS.RIGHT, KeyCodes.Right],
    'RETURN' : [KEYS.RETURN],
       'TAB' : [KEYS.TAB],
      'DOWN' : [KEYS.DOWN, KeyCodes.Down],
        'UP' : [KEYS.UP, KeyCodes.Up],
    'ESCAPE' : [KEYS.ESC]
  }

});

copy_properties(KeyEventController.prototype, {

  mapKey : function(k) {
    if (typeof(k) == 'number') {
      return [k];
    }

    if (KeyEventController.keyCodeMap[k.toUpperCase()]) {
      return KeyEventController.keyCodeMap[k.toUpperCase()];
    }

    var l = k.charCodeAt(0);
    var u = k.toUpperCase().charCodeAt(0);
    if (l != u) {
      return [l, u];
    }

    return [l];
  },

  onkeyevent : function(type, e) {
    e = event_get(e);

    var evt = null;
    var handlers = this.handlers[e.keyCode];
    var callback, filter, abort;

    if (handlers) {
      for (var ii = 0; ii < handlers.length; ii++) {
        callback = handlers[ii].callback;
        filter   = handlers[ii].filter;
        
        try {
          if (!filter || filter(e, type)) {
            abort = callback(e, type);
            if (abort === false) {
              return event_abort(e) || event_prevent(e);
            }
          }
        } catch (exception) {
          Util.error('Uncaught exception in key handler: %x', exception);
        }
      }
    }

    return true;
  }

});


  /**************  editor.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/


function editor_two_level_change(selector, subtypes_array, sublabels_array)
{
  selector = ge(selector);
  if ( selector.getAttribute("typefor") )
    subselector = ge(selector.getAttribute("typefor"));

  if ( selector && subselector ) {
    // Clear Old Options
    subselector.options.length = 1;
    type_value = selector.options[selector.selectedIndex].value;

    if ( type_value == "") {
      type_value = -1;
    }

    // Fill with New Options
    index = 1;
    suboptions = subtypes_array[type_value];
    if (typeof(suboptions) != "undefined") {
      for (var key = 0; key < suboptions.length; key++) {
        if (typeof(suboptions[key]) != "undefined") {
          subselector.options[index++] = new Option(suboptions[key], key);
        }
      }
    }

    if (sublabels_array)  {
        if (sublabels_array[type_value]) {
            subselector.options[0] = new Option(sublabels_array[type_value], "");
            subselector.options[0].selected = true;
        } else {
            subselector.options[0] = new Option("---", "");
            subselector.options[0].selected = true;
        }
    }

    // Potentially Disable Subtype Selector
    subselector.disabled = subselector.options.length <= 1;
  }
}

function editor_two_level_set_subselector(subselector, value)
{
  subselector = ge(subselector);
  if ( subselector ) {
    opts = subselector.options;
    for ( var index=0; index < opts.length; index++ ) {
      if ((opts[index].value == value) || ( value === null && opts[index].value == '' )) {
        subselector.selectedIndex = index;
      }
    }
  }
}

function editor_network_change(selector, prefix, orig_value) {
  selector = ge(selector);
  if ( selector && selector.value > 0 ) {
    // these values are hard-coded, which is not great. but it works, which is good.
    show('display_network_message');
  } else {
    hide('display_network_message');
  }
}

function editor_rel_change(selector, prefix, orig_value)
{
  selector = ge(selector);

  for ( var rel_type = 2; rel_type <= 6; rel_type++ ) {
    if ( rel_type == selector.value ) {
      show(prefix+'_new_partner_'+rel_type);
    } else {
      hide(prefix+'_new_partner_'+rel_type);
    }
  }

  // Show New Partner Box
  if ( selector && ge(prefix+'_new_partner') ) {
    if ( selector.value > 1 ) {
      show(prefix+'_new_partner');
    } else {
      hide(prefix+'_new_partner');
    }

  }

  // Cancel or Uncancel Relationship based on new status value
  if ( selector && ge(prefix+'_rel_uncancel') ) {
    if ( selector.value > 1 )
      editor_rel_uncancel(selector, prefix, selector.value);
    else
      editor_rel_cancel(selector, prefix);
  }

  // Toggle Awaiting
  editor_rel_toggle_awaiting(selector, prefix, orig_value);
}

function rel_typeahead_onsubmit() {
  return false;
}

function rel_typeahead_onselect(friend) {
  if (!friend)
    return;
  $('new_partner').value = friend.i;
}

function editor_rel_toggle_awaiting(selector, prefix, orig_value)
{
  // Toggle awaiting or required notices based on orig_value
  selector = ge(selector);
  if ( selector && ge(prefix+'_rel_required') ) {
    if ( selector.value == orig_value ) {
      hide(prefix+'_rel_required');
      show(prefix+'_rel_awaiting');
    }
    else {
      show(prefix+'_rel_required');
      hide(prefix+'_rel_awaiting');
    }
  }
}

function editor_rel_cancel(selector, prefix)
{
  if ( ge(prefix+'_rel_uncancel') )
    show(prefix+'_rel_uncancel');
  if ( ge(prefix+'_rel_cancel') )
    hide(prefix+'_rel_cancel');
  selector = ge(selector);
  if ( ge(selector) && $(selector).selectedIndex > 1 )
    editor_rel_set_value(selector, 1);
}

function editor_rel_uncancel(selector, prefix, rel_value)
{
  if ( ge(prefix+'_rel_uncancel') )
    hide(prefix+'_rel_uncancel');
  if ( ge(prefix+'_rel_cancel') )
    show(prefix+'_rel_cancel');

  if ( rel_value == 4 || rel_value == 5 ) {
    hide(prefix+'_rel_with');
    show(prefix+'_rel_to');
  } else if ( rel_value > 1 ) {
    show(prefix+'_rel_with');
    hide(prefix+'_rel_to');
  }

  if ( ge(selector) && $(selector).selectedIndex <= 1 )
    editor_rel_set_value(selector, rel_value);
  editor_rel_toggle_awaiting(selector, prefix, rel_value);
}

function editor_autocomplete_onselect(result) {
  var hidden=ge(/(.*)_/.exec(this.obj.name)[1] + '_id');
  if (result) {
    hidden.value=result.i==null ? result.t : result.i;
  }
  else {
    hidden.value=-1;
  }
}

function editor_rel_set_value(selector, value)
{
  selector = ge(selector);
  if ( selector ) {
    opts = selector.options;
    opts_length = opts.length;
    for ( var index=0; index < opts_length; index++ ) {
      if ((opts[index].value == value) || ( value === null && opts[index].value == '' )) {
        selector.selectedIndex = index;
      }
    }
  }
}

function enableDisable(gainFocus, loseFocus) {
    loseFocus = ge(loseFocus);
    if (loseFocus) {
        if (loseFocus.value) loseFocus.value = "";
        if (loseFocus.selectedIndex) loseFocus.selectedIndex= 0;
    }
}

function show_editor_error(error_text, exp_text)
{
    $('editor_error_text').innerHTML = error_text;
    $('editor_error_explanation').innerHTML = exp_text;
    show('error');
}

function make_explanation_list(list, num, type) {
  var exp = '';
  if (type == 'missing') {
    if (num == 1) {
      exp = tx('el01', {'thing-1': list[0]});
    } else if (num == 2) {
      exp = tx('el02', {'thing-1': list[0], 'thing-2': list[1]});
    } else if (num == 3) {
      exp = tx('el03', {'thing-1': list[0], 'thing-2': list[1], 'thing-3': list[2]});
    } else if (num == 4) {
      exp = tx('el04', {'thing-1': list[0], 'thing-2': list[1], 'thing-3': list[2], 'thing-4': list[3]});
    } else if (num > 4) {
      exp = tx('el05', {'thing-1': list[0], 'thing-2': list[1], 'thing-3': list[2], 'num': num-3});
    }
  } else if (type == 'bad') {
    if (num == 1) {
      exp = tx('el06', {'thing-1': list[0]});
    } else if (num == 2) {
      exp = tx('el07', {'thing-1': list[0], 'thing-2': list[1]});
    } else if (num == 3) {
      exp = tx('el08', {'thing-1': list[0], 'thing-2': list[1], 'thing-3': list[2]});
    } else if (num == 4) {
      exp = tx('el09', {'thing-1': list[0], 'thing-2': list[1], 'thing-3': list[2], 'thing-4': list[3]});
    } else if (num > 4) {
      exp = tx('el10', {'thing-1': list[0], 'thing-2': list[1], 'thing-3': list[2], 'num': num-3});
    }
  }
  return exp;
}

function TimeSpan(start_prefix, end_prefix, span, auto) {

    // Public Methods

    //gets the timestamp from the start date fields
    this.get_start_ts = function () {
        return _get_date_time_ts(_start_month, _start_day, _start_year,
                _start_hour, _start_min, _start_ampm);
    }

    //gets the current timestamp from the end date fields
    this.get_end_ts = function () {
        var start_ts = _get_date_time_ts(_start_month, _start_day, _start_year,
                _start_hour, _start_min, _start_ampm);
        var end_ts   = _get_date_time_ts(_end_month, _end_day, _end_year,
                _end_hour, _end_min, _end_ampm);
        if (start_ts > end_ts && !(_start_year && _end_year)) {
            //push end_ts to the future by a year
            var future_date = new Date();
            future_date.setTime(end_ts);
            future_date.setFullYear(future_date.getFullYear() + 1);
            return future_date.getTime();
        } else {
            return end_ts;
        }
    }

    // Private Variables and Methods

    var _start_month = ge(start_prefix+'_month');
    var _start_day = ge(start_prefix+'_day');
    var _start_hour = ge(start_prefix+'_hour');
    var _start_year = ge(start_prefix+'_year');
    var _start_min = ge(start_prefix+'_min');
    var _start_ampm = ge(start_prefix+'_ampm');

    var _end_month = ge(end_prefix+'_month');
    var _end_day = ge(end_prefix+'_day');
    var _end_year = ge(end_prefix+'_year');
    var _end_hour = ge(end_prefix+'_hour');
    var _end_min = ge(end_prefix+'_min');
    var _end_ampm = ge(end_prefix+'_ampm');

    var _bottom_touched;
    if (auto) {
        _bottom_touched = false;
    } else {
        _bottom_touched = true;
    }

    var _start_touched  = function() {
        if (!_bottom_touched) {
            _propogate_time_span(_start_month, _start_day, _start_year,
                    _start_hour, _start_min, _start_ampm);
        }
    }

    var _end_touched = function () {
        _bottom_touched = true;
    }

    var _propogate_time_span = function () {
        // 1) make the timestamp
        var start_ts = _get_date_time_ts(_start_month, _start_day, _start_year,
                                          _start_hour, _start_min, _start_ampm);

        // 2) make the offset timeSpan
        var end_ts = start_ts + span * 60000; //60,000 milis in minute

        // 3) propogate the endtime
        _set_date_time_from_ts(end_ts, _end_month, _end_day, _end_year,
                _end_hour, _end_min, _end_ampm);
    }

    var _get_date_time_ts = function (m, d, y, h, min, ampm) {

        var this_date = new Date();
        var date_this_day = this_date.getDate();
        var date_this_month = this_date.getMonth();
        var date_this_year = this_date.getFullYear();

        var month = m.value-1;
        var date = d.value;
        var hour;
        var minutes = min.value;
        var year;

        hour = parseInt(h.value);
        if (ampm.value != '') {
          // am or pm; otherwise this is a 24-hour time
          if (hour == 12) hour = 0;
          if (ampm.value == 'pm') {
              hour = hour + 12;
          }
        }

        //below infers the year from current time
        if (!y) {
            if (month < date_this_month) {
                year = date_this_year + 1;
            } else {
                if (month == date_this_month && date < date_this_day) {
                    year = date_this_year + 1;
                } else {
                    year = date_this_year;
                }
            }
        } else {
            year = y.value;
        }

        var new_date = new Date(year, month, date, hour, minutes, 0, 0);
        var ts = new_date.getTime();

        return ts;
    }

    var _set_date_time_from_ts = function (ts, m, d, y, h, min, ampm) {

        var new_date = new Date();
        new_date.setTime(ts);

        var old_month = m.value;

        var new_month   = new_date.getMonth() + 1; //not zero indexed
        var new_day     = new_date.getDate();
        var new_hour    = new_date.getHours();
        var new_minutes = new_date.getMinutes();
        var new_year    = new_date.getFullYear();
        var new_ampm;

        if (ampm.value != '') {
          if (new_hour > 11) {
              new_ampm = 'pm';
              if (new_hour > 12) {
                  new_hour = new_hour - 12;
              }
          } else {
              if (new_hour == 0) new_hour = 12;
              new_ampm = 'am';
          }
        } else {
          // 24-hour time
          new_ampm = '';
        }


        if (new_minutes < 10) {
            // handle case where new_minutes = "05"
            new_minutes = "0" + new_minutes;
        }

        m.value = new_month;
        d.value = new_day;
        if (y) {
            y.value = new_year;
        }
        h.value = new_hour;
        min.value = new_minutes;
        ampm.value = new_ampm;

        if (old_month != new_month) {
            //changing month, make sure our days are good
            editor_date_month_change(m, d, y ? y : false);
        }

    }

    var _start_month_touched = function() {
        _start_touched();
        editor_date_month_change(_start_month, _start_day, _start_year ? _start_year : false);
    }

    var _end_month_touched = function() {
        _end_touched();
        editor_date_month_change(_end_month, _end_day, _end_year ? _end_year : false);
    }

    //set the event handlers
    _start_month.onchange = _start_month_touched;
    _start_day.onchange = _start_touched;
    if (_start_year) {
        _start_year.onchange = _start_touched;
    }
    _start_hour.onchange = _start_touched;
    _start_min.onchange = _start_touched;
    _start_ampm.onchange = _start_touched;

    _end_month.onchange = _end_month_touched;
    _end_day.onchange = _end_touched;
    if (_end_year) {
        _end_year.onchange = _end_touched;
    }
    _end_hour.onchange = _end_touched;
    _end_min.onchange = _end_touched;
    _end_ampm.onchange = _end_touched;
}

function editor_date_month_change(month_el, day_el, year_el) {
  var month_el = ge(month_el);
  var day_el = ge(day_el);
  var year_el = year_el ? ge(year_el) : false;

  var new_num_days = month_get_num_days(month_el.value, year_el.value && year_el.value!=-1 ? year_el.value : false);
  var b = day_el.options[0].value==-1 ? 1 : 0; // if there's a blank day placeholder to worry about

  for (var i = day_el.options.length; i > new_num_days + b; i--) {
    remove_node(day_el.options[i - 1]);
  }
  for (var i = day_el.options.length; i < new_num_days + b; i++) {
    day_el.options[i] = new Option(i + (b ? 0 : 1));
  }
}

function editor_date_year_change(month, day, year) {
  editor_date_month_change(month, day, year);
}

/* Number of days in a given month and year.
 * If month or year aren't known, we err high (giving the user more days to choose from)
 * by returning 31 days for unknown month, and assuming a leap year for unknown year
 */
function month_get_num_days(month, year) {
  var temp_date;
  if (month == -1) {
    return 31;
  }
  temp_date = new Date(year ? year : 1912, month, 0);
  return temp_date.getDate();
}

function toggleEndWorkSpan(prefix) {
    if (shown(prefix+'_endspan')) {
        hide(prefix+'_endspan');
        show(prefix+'_present');
    } else {
        show(prefix+'_endspan');
        hide(prefix+'_present');
    }
}

function regionCountryChange(label_id, country_id, region_id, label_prefix) {
    switch (country_id) {
        case '326': //canada
            show(region_id);
            $(label_id).innerHTML = label_prefix + tx('el13');
        break;
        case '398': //usa
            show(region_id);
            $(label_id).innerHTML = label_prefix + tx('el12');
        break;
        default:
            $(label_id).innerHTML = label_prefix + tx('el11');
            hide(region_id);
        break;
    }
}

function regionCountryChange_twoLabels(country_label_id, region_label_id, country_id, region_id, label_prefix) {

    show(country_label_id);
    $(country_label_id).innerHTML = label_prefix + tx('el11');

    switch (country_id) {
        case '326': // canada
            show(region_id);
            show(region_label_id);
            $(region_label_id).innerHTML = label_prefix + tx('el13');
        break;
        case '':  // we still show US states when country is blank
        case '398': // usa
            show(region_id);
            show(region_label_id);
            $(region_label_id).innerHTML = label_prefix + tx('el12');
        break;
        default:
            $(region_label_id).innerHTML = label_prefix + tx('el12');
            $(region_id).disabled = true;
        break;
    }

}

// If a user picks a US state but a country isn't chosen, this will
// automatically set the country to US.
// This can happen because we default the country to empty, but still
// populate the region select with US states.
function regionCountyChange_setUSifStateChosen(country_select_id, region_select_id) {
  region_select = ge(region_select_id);
  country_select = ge(country_select_id);
  if (region_select.value != '' &&
      country_select.value == '') {
    country_select.value = 398;
  }
}

function regionCountryChange_restrictions(country_select_id, region_select_id) {
        country_select = ge(country_select_id);
        if (country_select.value == 398) {//ignore U.S. country query
            country_select.value = '';
         } else if (country_select.value == 326) {// ignore Canada country query if province is present
               region_select = ge(region_select_id);
               if (region_select.value) {
                    country_select.value = '';
               }
         }
}

function textLimit(ta, count) {
  var text = ge(ta);
  if (text.value.length > count) {
    text.value = text.value.substring(0,count);
    if (arguments.length>2) { // id of an error block is defined
      $(arguments[2]).style.display='block';
    }
  }
}

function textLimitStrict(text_id, limit, message_id, count_id, submit_id) {
  var text = ge(text_id);
  var len = text.value.length;
  var diff = len - limit;
  if (diff > 0) {
    if (diff > 25000) {
      text.value = text.value.substring(0, limit + 25000);
      diff = 25000;
    }
    $(message_id).style.display='block';
    $(count_id).innerHTML = diff;
    $(submit_id).disabled = true;
  } else if (len == 0) { //empty comment
    $(message_id).style.display = 'none';
    $(submit_id).disabled = true;
    $(count_id).innerHTML = 1;
  } else {
    if ($(count_id).innerHTML != 0) {
      $(count_id).innerHTML = 0;
      $(message_id).style.display = 'none';
      $(submit_id).disabled = false;
    }
  }
}

function calcAge(month_el, day_el, year_el) {
  bYear  = parseInt($(year_el).value);
  bMonth = parseInt($(month_el).value);
  bDay   = parseInt($(day_el).value);

  theDate = new Date();
  year    = theDate.getFullYear();
  month   = theDate.getMonth() + 1;
  day     = theDate.getDate();

  age = year - bYear;
  if ((bMonth > month) || (bMonth == month && day < bDay)) age--;

  return age;
}

function mobile_phone_nag(words, obj, anchor) {
  var nagged = false;
  var callback = function() {
    if (nagged) {
      return;
    }
    for (var i = 0; i < words.length; i++) {
      if ((new RegExp('\\b'+words[i]+'\\b', 'i')).test(obj.value)) {
        nagged = true;
        (new AsyncRequest())
          .setURI('/ajax/mobile_phone_nag.php')
          .setHandler(function(async) {
            var html = async.getPayload();
            if (html) {
              var div = document.createElement('div');
              div.innerHTML = html;
              div.className = 'mobile_nag';
              div.style.display = 'none';
              anchor.parentNode.insertBefore(div, anchor);
              animation(div).blind().show().from('height', 0).to('height', 'auto').go();
            }
          })
          .setReadOnly(true)
          .setOption('suppressErrorHandlerWarning', true)
          .send();
        break;
      }
    }
  }

  addEventBase(obj, 'keyup', callback);
  addEventBase(obj, 'change', callback);
}

function mobile_phone_nag_hide(obj) {
  while (obj.parentNode && obj.className != 'mobile_nag') {
    obj = obj.parentNode;
  }
  obj.parentNode.removeChild(obj);
}



  /**************  timezone.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/*
 * Obtains timezone in the form of offset from GMT in mins.
 * This offset is what needs to be added to the local time
 * to get to GMT (and subtracted from GMT to
 * get to local time)
 * @param timestamp - the server timestamp
 * @author eugene
 */
function tz_calculate( timestamp ) {
  var d = new Date();
  var raw_offset = d.getTimezoneOffset() / 30;

  var time_sec  = d.getTime() / 1000;
  // figure out when the user is manually setting the time
  // to deal with timezones ... tsk tsk
  var time_diff = Math.round( ( timestamp - time_sec ) / 1800 );

  var rounded_offset = Math.round( raw_offset + time_diff ) % 48;

  // confine to range [-28, 24], inclusive, corresponding to GMT-12 to GMT+14
  if (rounded_offset == 0) {
    return 0;
  } else if (rounded_offset > 24) {
    rounded_offset -= Math.ceil(rounded_offset / 48) * 48;
  } else if (rounded_offset < -28) {
    rounded_offset += Math.ceil(rounded_offset / -48) * 48;
  }

  return rounded_offset * 30;
}

/*
 * Given a timezone form, submits it, calling
 * tz_calculate to add the gmt_offset parameter
 * @param  tzForm form   timezone form DOM object
 * @author eugene
 */
function ajax_tz_set( tzForm ) {
  var timestamp   = tzForm.time.value;
  var gmt_off     = -tz_calculate(timestamp);

  var cur_gmt_off = tzForm.tz_gmt_off.value;
  if ( gmt_off != cur_gmt_off) {
    var ajaxUrl = '/ajax/autoset_timezone_ajax.php';
    new AsyncSignal( ajaxUrl,
                  { user: tzForm.user.value,
                    post_form_id: tzForm.post_form_id.value,
                    gmt_off: gmt_off
                  }
        ).send();
    // hmmm, what to do in case of failure
    // you can set a handler if you want to handle error
    // also, change ajax/autoset_timezone_ajax.php to setError
    // before sendAndExit
  }
}

/*
 * On-load handler for automatically setting a new user's timezone
 * @author eugene
 */
function tz_autoset() {
  var tz_form = ge('tz_autoset_form');
  if ( tz_form )
    ajax_tz_set( tz_form );
}

// onloadRegister( tz_autoset ); // apparently doesn't work in all browsers



  /**************  lib/ui/typeaheadpro.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides typeaheadpro tokenizer tokenizer-input token dynamic-custom-source
 *            custom-source regions-source language-source keywords-source
 *            time-source network-source concentration-souce
 *            friend-source friend-and-email-source friendlist-source
 *            static-source typeahead-source
 *  @requires event-extensions ua vector intl typeaheadpro-css
 */

//
// typeahead class. for... typing ahead
// =======================================================================================

function typeaheadpro(obj, source, properties) {

  // h4x. don't do u\a checking until we need to.
  if (!typeaheadpro.hacks) {
    // this hack is for missing keypress events. if you type really fast and hit enter at the same time as a letter it'll forget
    //   to send us a keypress for the enter and we can't cancel the form submit. this hack introduces another bug where if you hold down
    //   a key and the blur off the input you can't submit the form, but that's the lesser of two evils in this case.
    typeaheadpro.should_check_missing_events = ua.safari() < 500;
    // MSIE will make select boxes shine through our div unless we cover up with an iframe
    typeaheadpro.should_use_iframe =
    // Returning false in a keydown in Safari or IE means you will not get a keypress event (until the key repeat rate fires). However,
    //   we need to return false in the keydown to prevent the cursor from moving in the textbox.
    typeaheadpro.should_simulate_keypress = ua.ie() || (ua.safari() > 500 && ua.safari() < 523 || ua.safari() >= 525);
    // Opera \ Safari 2 doesn't support overflow-y (which we need to make Safari 3 work)
    typeaheadpro.should_use_overflow = ua.opera() < 9.5 || ua.safari() < 500;
    // Firefox doesn't give us magic keydown events when people type
    // CJK characters, so we can't turn on the input checker on demand.
    if (ua.firefox()) {
      this.poll_handle = setInterval(this.check_value.bind(this), 100);
      this.deactivate_poll_on_blur = false;
    }
    typeaheadpro.hacks = true;
  }

  // link a reference to this instance statically
  typeaheadpro.instances = (typeaheadpro.instances || []);
  typeaheadpro.instances.push(this);
  this.instance = typeaheadpro.instances.length - 1;

  // copy over supplied parameters
  copy_properties(this, properties || {});

  // setup pointers every which way
  this.obj = obj;
  this.obj.typeahead = this;

  // attach event listeners where needed
  this.obj.onfocus = this._onfocus.bind(this);
  this.obj.onblur = chain(this.obj.onblur, this._onblur.bind(this));
  this.obj.onchange = this._onchange.bind(this);

  this.obj.onkeyup = function(event) {
    return this._onkeyup(event || window.event);
  }.bind(this);

  this.obj.onkeydown = function(event) {
    return this._onkeydown(event || window.event);
  }.bind(this);

  this.obj.onkeypress = function(event) {
    return this._onkeypress(event || window.event);
  }.bind(this);

  // setup custom icon
  this.want_icon_list = false;
  this.showing_icon_list = false;
  this.stop_suggestion_select = false;


  if (this.typeahead_icon_class && this.typeahead_icon_get_return) {
    this.typeahead_icon = document.createElement('div');
    this.typeahead_icon.className = 'typeahead_list_icon ' + this.typeahead_icon_class;
    this.typeahead_icon.innerHTML = '&nbsp;';
    this.setup_typeahead_icon();
    // in FF doing setup_typeahead_icon() unfocuses the input b/c it moves it in the DOM.. so refocus
    setTimeout(function() { this.focus(); }.bind(this), 50);
    this.typeahead_icon.onmousedown = function(event) {
      return this.typeahead_icon_onclick(event || window.event);
    }.bind(this);
  }

  // setup container for results
  this.focused = this.obj.offsetWidth ? true : false;
  this.anchor = this.setup_anchor();
  this.dropdown = document.createElement('div');
  this.dropdown.className = 'typeahead_list';
  if (!this.focused) {
    this.dropdown.style.display = 'none';
  }
  this.anchor_block = this.anchor_block || this.anchor.tagName.toLowerCase() == 'div';
  if (this.should_use_absolute) {
    document.body.appendChild(this.dropdown);
    this.dropdown.className += ' typeahead_list_absolute';
  } else {
    // If the parent node is the wrapper we use so we can shift the bottom
    // border color of the input when there are results, add the dropdown
    // to our grandparent node rather than the parent so the wrapper contains
    // only the input.
    var us = this.anchor;
    var parent = us.parentNode;
    if (parent.id == 'qsearch_wrapper') {
      us = parent;
      parent = parent.parentNode;
    }
    if (us.nextSibling) {
      parent.insertBefore(this.dropdown, us.nextSibling);
    } else {
      parent.appendChild(this.dropdown);
    }
    if (!this.anchor_block) {
      parent.insertBefore(document.createElement('br'), this.dropdown);
    }
  }

  this.dropdown.appendChild(this.list = document.createElement('div'));
  this.dropdown.onmousedown = function(event) {
    return this.dropdown_onmousedown(event || window.event);
  }.bind(this);

  // iframe for hacky stuff
  if (typeaheadpro.should_use_iframe && !typeaheadpro.iframe) {
    typeaheadpro.iframe = document.createElement('iframe');
    typeaheadpro.iframe.src = "/common/blank.html";
    typeaheadpro.iframe.className = 'typeahead_iframe';
    typeaheadpro.iframe.style.display = 'none';
    typeaheadpro.iframe.frameBorder = 0;
    document.body.appendChild(typeaheadpro.iframe);
  }

  // set the iframe zIndex to one below the dropdown... to fix an issue with typeaheads in dialogs
  if (typeaheadpro.should_use_iframe && typeaheadpro.iframe) {
    typeaheadpro.iframe.style.zIndex = parseInt(get_style(this.dropdown, 'zIndex')) - 1;
  }

  // get this party started
  this.results_text = '';
  this.last_key_suggestion = 0;
  this.status = typeaheadpro.STATUS_BLOCK_ON_SOURCE_BOOTSTRAP;
  this.clear_placeholder();
  if (source) {
    this.set_source(source);
  }
  if (this.source) {
    this.selectedindex = -1;
    if (this.focused) {
      this.show();
      this._onkeyup();
      this.set_class('');
      this.capture_submit();
    }
  } else {
    this.hide();
  }
}
// don't change these
typeaheadpro.prototype.enumerate = false;
typeaheadpro.prototype.interactive = false;
typeaheadpro.prototype.changed = false;
typeaheadpro.prototype.render_block_size = 50;
typeaheadpro.prototype.typeahead_icon_class = false;
typeaheadpro.prototype.typeahead_icon_get_return = false;
typeaheadpro.prototype.old_value = "";
typeaheadpro.prototype.poll_handle = null;
typeaheadpro.prototype.deactivate_poll_on_blur = true;
typeaheadpro.prototype.suggestion_count = 0;
typeaheadpro.STATUS_IDLE = 0;
typeaheadpro.STATUS_WAITING_ON_SOURCE = 1;
typeaheadpro.STATUS_BLOCK_ON_SOURCE_BOOTSTRAP = 2;

// ok to change these
typeaheadpro.prototype.should_use_absolute = false;
typeaheadpro.prototype.max_results = 0;
typeaheadpro.prototype.max_display = 10;
typeaheadpro.prototype.allow_placeholders = true;
typeaheadpro.prototype.auto_select = true;

// set a source for this typeahead
typeaheadpro.prototype.set_source = function(source) {
  this.source = source;
  this.source.set_owner(this);
  this.status = typeaheadpro.STATUS_IDLE;
  this.cache = {};
  this.last_search = 0;
  this.suggestions = [];
}

// grab the anchor for the typeahead list
typeaheadpro.prototype.setup_anchor = function() {
  return this.obj;
}

// destroys this typeahead instance
typeaheadpro.prototype.destroy = function() {

  if (this.typeahead_icon) {
    this.typeahead_icon.parentNode.removeChild(this.typeahead_icon);
    this.toggle_icon_list = function () {};
  }

  this.clear_render_timeouts();
  if (!this.anchor_block && this.anchor.nextSibling.tagName.toLowerCase() == 'br') {
    this.anchor.parentNode.removeChild(this.anchor.nextSibling);
  }
  if (this.dropdown) {
    this.dropdown.parentNode.removeChild(this.dropdown);
  }

  // blank out the events because these can lag sometimes it seems
  this.obj.onfocus =
  this.obj.onblur =
  this.obj.onkeyup =
  this.obj.onkeydown =
  this.obj.onkeypress = null;

  // pull it out the dom
  this.obj.parentNode.removeChild(this.obj);

  // clear up pointers
  this.anchor =
  this.obj =
  this.obj.typeahead =
  this.dropdown = null;
  delete typeaheadpro.instances[this.instance];
}

// check for changes to the value; needed because Asian input
// methods don't fire JS events when the user finishes composing a
// multi-keystroke character on all browsers, and sometimes fire
// events when the user is in the middle of entering a character.
typeaheadpro.prototype.check_value = function() {
  if (this.obj) {
    var new_value = this.obj.value;
    if (new_value != this.old_value) {
      this.dirty_results();
      this.old_value = new_value;
    }
  }
}

// event handler when the input box receives a key press
typeaheadpro.prototype._onkeyup = function(e) {
  this.last_key = e ? e.keyCode : -1;

  // safari h4x
  if (this.key_down == this.last_key) {
    this.key_down = 0;
  }

  switch (this.last_key) {
    case 27: // esc
      this.selectedindex = -1;
      this._onselect(false);
      this.hide();
      break;
  }
}

// event handler when a key is pressed down on the text box
typeaheadpro.prototype._onkeydown = function(e) {
  this.key_down = this.last_key=e ? e.keyCode : -1;
  this.interactive = true;

  switch (this.last_key) {
    case 33:
    case 34:
    case 38:
    case 40:
      if (typeaheadpro.should_simulate_keypress) {
        this._onkeypress({keyCode: this.last_key});
      }
      return false;

    case 9: // tab
      this.select_suggestion(this.selectedindex);
      this.advance_focus();
      break;

    case 13: // enter
     if (this.select_suggestion(this.selectedindex)) {
       this.hide();
     }
     // we capture the return of _onsubmit here and return it onkeypress to prevent the form from submitting
     if (typeof(this.submit_keydown_return) != 'undefined') {
       this.submit_keydown_return = this._onsubmit(this.get_current_selection());
     }
     return this.submit_keydown_return;

    case 229:
      // IE and Safari send this fake keycode to indicate we're in an IME
      // compose state. Since we won't necessarily get an event when the
      // user selects a character after composing it, start polling the
      // input to see if it has changed.
      if (!this.poll_handle) {
        this.poll_handle = setInterval(this.check_value.bind(this), 100);
      }
      break;

    default:
      // Safari doesn't give us a key-down on backspace, etc.
      setTimeout(bind(this, 'check_value'), 10);
  }
}

// event handler for when a key is pressed
typeaheadpro.prototype._onkeypress = function(e) {
  var multiplier = 1;
  this.last_key = e ? event_get_keypress_keycode(e) : -1;
  this.interactive = true;

  switch (this.last_key) {
    case 33: // page up
      multiplier = this.max_display;
      // fallthrough
    case 38: // up
      this.set_suggestion(multiplier > 1 && this.selectedindex > 0 && this.selectedindex < multiplier ? 0 : this.selectedindex - multiplier);
      this.last_key_suggestion = (new Date()).getTime();
      return false;

    case 34: // page down
      multiplier = this.max_display;
      // fallthrough
    case 40: // down
      if (trim(this.get_value()) == '' && !this.enumerate) {
        this.enumerate = true;
        this.results_text = null;
        this.dirty_results();
      } else {
        this.set_suggestion(this.suggestions.length <= this.selectedindex + multiplier ? this.suggestions.length - 1 : this.selectedindex + multiplier);
        this.last_key_suggestion = (new Date()).getTime();
      }
      return false;

    case 13: // enter
      var ret = null;
      if (typeof(this.submit_keydown_return) == 'undefined') {
        ret = this.submit_keydown_return = this._onsubmit(this.get_current_selection());
      } else {
        ret = this.submit_keydown_return;
        delete this.submit_keydown_return;
      }
      return ret;

    default:
      // Key isn't part of the value yet, so do the typeahead logic
      // after the element state is updated (which happens after this
      // event handler returns.)
      setTimeout(bind(this, 'check_value'), 10);
      break;
  }
  return true;
}

// mostly used for compatibility with mobile browsers
typeaheadpro.prototype._onchange = function() {
  this.changed = true;
}

// event handler when a match is found (happens a lot)
typeaheadpro.prototype._onfound = function(obj) {
  return this.onfound ? this.onfound.call(this, obj) : true;
}

// event handler when the user submits the form
typeaheadpro.prototype._onsubmit = function(obj) {
  if (this.onsubmit) {
    var ret = this.onsubmit.call(this, obj);

    if (ret && this.obj.form) {
      if (!this.obj.form.onsubmit || this.obj.form.onsubmit()) {
        this.obj.form.submit();
      }
      return false;
    }
    return ret;
  } else {
    this.advance_focus();
    return false;
  }
}

// event handler when the user selects a suggestions
typeaheadpro.prototype._onselect = function(obj) {
  if (this.onselect) {
    this.onselect.call(this, obj);
  }
}

// event handler when obj gets focus
typeaheadpro.prototype._onfocus = function() {
  if (this.last_dropdown_mouse > (new Date()).getTime() - 10 || this.focused) {
    return;
  }
  this.focused = true;
  this.changed = false;
  this.clear_placeholder();
  this.results_text = '';
  this.set_class('');
  this.dirty_results();
  this.show();
  this.capture_submit();
  if (this.typeahead_icon) {
    show(this.typeahead_icon);
  }
}

// event handler when focus is lost
typeaheadpro.prototype._onblur = function(event) {
  if (!this.stop_hiding) {
    if (this.showing_icon_list) {
      this.toggle_icon_list(true);
    }
  } else {
    this.focus();
    return false;
  }

  if (this.last_dropdown_mouse && this.last_dropdown_mouse > (new Date()).getTime() - 10) {
    event_prevent(event);
    setTimeout(function() { this.focus() }.bind(this.obj), 0);
    return false;
  }

  this.focused = false;
  if (this.changed && !this.interactive) {
    this.dirty_results();
    this.changed = false;
    return;
  }

  if (!this.suggestions) {
    this._onselect(false);
  } else if (this.selectedindex >= 0) {
    this.select_suggestion(this.selectedindex);
  }
  this.hide();
  this.update_class();
  if (!this.get_value()) {
    var noinput = this.allow_placeholders ? '' : this.source.gen_noinput();
    this.set_value(noinput ? noinput : '');
    this.set_class('typeahead_placeholder')
  }

  if (this.poll_handle && this.deactivate_poll_on_blur) {
    clearInterval(this.poll_handle);
    this.poll_handle = null;
  }
}

typeaheadpro.prototype.typeahead_icon_onclick = function(event) {
  this.stop_hiding = true;
  this.focus();
  setTimeout(function() { this.toggle_icon_list(); }.bind(this), 50);
  event_abort(event);
  return false;
}

// this function exists because IE7 doesn't let us override mousedown events on the scrollbar
typeaheadpro.prototype.dropdown_onmousedown = function(event) {
  this.last_dropdown_mouse = (new Date()).getTime();
}

typeaheadpro.prototype.setup_typeahead_icon = function() {
  this.typeahead_parent = document.createElement('div');
  this.typeahead_parent.className = 'typeahead_parent';
  this.typeahead_parent.appendChild(this.typeahead_icon);
  this.obj.parentNode.insertBefore(this.typeahead_parent, this.obj);
}

// event handler for mousemove \ mouseout
typeaheadpro.prototype.mouse_set_suggestion = function(index) {
  if (!this.visible) {
    return;
  }
  if ((new Date()).getTime() - this.last_key_suggestion > 50) {
    this.set_suggestion(index);
  }
}

// steals the submit event of the parent form (if any). see should_check_missing_events
typeaheadpro.prototype.capture_submit = function() {
  if (!typeaheadpro.should_check_missing_events) return;

  if ((!this.captured_form || this.captured_substitute != this.captured_form.onsubmit) && this.obj.form) {

    this.captured_form = this.obj.form;
    this.captured_event = this.obj.form.onsubmit;
    this.captured_substitute = this.obj.form.onsubmit = function() {
      return ((this.key_down && this.key_down!=13 && this.key_down!=9) ? this.submit_keydown_return : (this.captured_event ? this.captured_event.apply(arguments, this.captured_form) : true)) ? true : false;
    }.bind(this);
  }
}

// sets the current selected suggestion. error checking is done here, so you can pass this pretty much anything.
typeaheadpro.prototype.set_suggestion = function(index) {
  this.stop_suggestion_select = false;
  if (!this.suggestions || this.suggestions.length <= index) { return }
  var old_node = this.get_suggestion_node(this.selectedindex);
  this.selectedindex = (index <= -1) ? -1 : index;
  var cur_node = this.get_suggestion_node(this.selectedindex);

  if (old_node) {
    old_node.className = old_node.className.replace(/\btypeahead_selected\b/, 'typeahead_not_selected');
  }
  if (cur_node) {
    cur_node.className = cur_node.className.replace(/\btypeahead_not_selected\b/, 'typeahead_selected');
  }
  this.recalc_scroll();

  this._onfound(this.get_current_selection());
}

// returns the list child node for a particular index
typeaheadpro.prototype.get_suggestion_node = function(index) {
  var nodes = this.list.childNodes;
  return index == -1 ? null : nodes[Math.floor(index / this.render_block_size)].childNodes[index % this.render_block_size];
}

// gets the current selection
typeaheadpro.prototype.get_current_selection = function() {
  return this.selectedindex == -1 ? false : this.suggestions[this.selectedindex];
}

// sets the class if we've found a suggestions
typeaheadpro.prototype.update_class = function() {
  if (this.suggestions && this.selectedindex!=-1 && typeahead_source.flatten_string(this.get_current_selection().t) == typeahead_source.flatten_string(this.get_value())) {
    this.set_class('typeahead_found');
  } else {
    this.set_class('');
  }
}

// selects this suggestion... it's a done deal
typeaheadpro.prototype.select_suggestion = function(index) {
  if (!this.stop_suggestion_select && this.current_selecting != index) {
    this.current_selecting = index;
    }
  if (!this.suggestions || index == undefined || index === false || this.suggestions.length <= index || index < 0) {
    this._onfound(false);
    this._onselect(false);
    this.selectedindex = -1;
    this.set_class('');
  } else {
    this.selectedindex = index;
    this.set_value(this.suggestions[index].t);
    this.set_class('typeahead_found');
    this._onfound(this.suggestions[this.selectedindex]);
    this._onselect(this.suggestions[this.selectedindex]);
  }
  if (!this.interactive) {
    this.hide();
    this.blur();
  }
  this.current_selecting = null;
  return true;
}

// sets the value of the input
typeaheadpro.prototype.set_value = function(value) {
  this.obj.value = value;
}

// gets the value of the input
typeaheadpro.prototype.get_value = function() {
  if (this.showing_icon_list && this.old_typeahead_value != this.obj.value) {
    // hide the icon list because the user is typing something
    this.toggle_icon_list();
  }
  if (this.want_icon_list) {
    return this.typeahead_icon_get_return;
  } else {
    if (this.showing_icon_list) {
      // hide
      this.toggle_icon_list();
    }
  }
  return this.obj.value;
}

// called by source in response to search_value
typeaheadpro.prototype.found_suggestions = function(suggestions, text, fake_data) {

  if (!suggestions) {
    suggestions = [];
  }

  // record the number of suggestions for use by subclasses
  this.suggestion_count = suggestions.length;

  if (!fake_data) {
    this.status = typeaheadpro.STATUS_IDLE;
    this.add_cache(text, suggestions);
  }
  this.clear_render_timeouts();

  // if this is a duplicate call we can skip it
  if (this.get_value() == this.results_text) {
    return;
  } else if (!fake_data) {
    this.results_text = typeahead_source.flatten_string(text);
    if (this.enumerate && trim(this.results_text) != '') {
      this.enumerate = false;
    }
  }

  // go through the new and old selections and figure out if the currently highlighted
  // suggestion is in the new results. if so, we highlight it after the update.
  var current_selection = -1;
  if (this.selectedindex != -1) {
    var selected_id = this.suggestions[this.selectedindex].i;
    for (var i = 0, l = suggestions.length; i < l; i++) {
      if (suggestions[i].i == selected_id) {
        current_selection = i;
        break;
      }
    }
  }
  if (current_selection == -1 && this.auto_select && suggestions.length) {
    current_selection = 0;
    this._onfound(suggestions[0]);
  }
  this.selectedindex = current_selection;
  this.suggestions = suggestions;
  if (!fake_data) {
    this.real_suggestions = suggestions;
  }

  if (suggestions.length) {
    var html = [],
        blocks = Math.ceil(suggestions.length / this.render_block_size),
        must_render = {},
        firstblock,
        samplenode = null;
    this.list.innerHTML = ''; // clear the old the suggestions
    for (var i = 0; i < blocks; i++) {
      this.list.appendChild(document.createElement('div'));
    }
    // figure out which blocks we need to render first
    if (current_selection > -1) {
      firstblock = Math.floor(current_selection / this.render_block_size);
      // always render the block the user is currently selecting
      must_render[firstblock] = true;
      // and the next closest one
      if (current_selection % this.render_block_size > this.render_block_size / 2) {
        must_render[firstblock + 1] = true;
      } else if (firstblock != 0) {
        must_render[firstblock - 1] = true;
      }
    } else {
      must_render[0] = true;
    }

    // render the blocks that the user might see
    for (var node in must_render) {
      this.render_block(node);
      sample = this.list.childNodes[node].firstChild;
    }
    this.show();

    // and schedule rendering of the other ones
    if (blocks) {
      var suggestion_height = sample.offsetHeight;
      this.render_timeouts = [];
      for (var i = 1; i < blocks; i++) {
        if (!must_render[i]) {
          this.list.childNodes[i].style.height = suggestion_height * Math.min(this.render_block_size, suggestions.length - i * this.render_block_size) + 'px';
          this.list.childNodes[i].style.width = '1px';
          this.render_timeouts.push(setTimeout(this.render_block.bind(this, i), 700 + i * 50)); // render blocks 750ms later
        }
      }
    }
  } else {
    this.selectedindex = -1;
    this.set_message(this.status == typeaheadpro.STATUS_IDLE ? this.source.gen_nomatch() : this.source.gen_loading());
    this._onfound(false);
  }
  this.recalc_scroll();

  if (!fake_data && this.results_text != typeahead_source.flatten_string(this.get_value())) {
    this.dirty_results();
  }
}

// render a block of suggestions into the list
typeaheadpro.prototype.render_block = function(block, stack) {
  var suggestions = this.suggestions,
      selectedindex = this.selectedindex,
      text = this.get_value(),
      instance = this.instance,
      html = [],
      node = this.list.childNodes[block];
  for (var i = block * this.render_block_size, l = Math.min(suggestions.length, (block + 1) * this.render_block_size); i < l; i++) {
    html.push('<div class="');
    if (selectedindex == i) {
      html.push('typeahead_suggestion typeahead_selected');
    } else {
      html.push('typeahead_suggestion typeahead_not_selected');
    }
    html.push('" onmouseover="typeaheadpro.instances[', instance, '].mouse_set_suggestion(', i, ')" ',
                'onmousedown="typeaheadpro.instances[', instance, '].select_suggestion(', i, '); event_abort(event);">',
              this.source.gen_html(suggestions[i], text), '</div>');
  }
  node.innerHTML = html.join('');
  node.style.height = 'auto';
  node.style.width = 'auto';
}

// if there's render timeouts still pending cancel them
typeaheadpro.prototype.clear_render_timeouts = function() {
  if (this.render_timeouts) {
    for (var i = 0; i < this.render_timeouts.length; i++) {
      clearTimeout(this.render_timeouts[i]);
    }
    this.render_timeouts = null;
  }
}

// shrink the typeahead list to make a scroll bar
typeaheadpro.prototype.recalc_scroll = function() {
  var cn = this.list.firstChild;
  if (!cn) {
    return;
  }

  if (cn.childNodes.length > this.max_display) { // this assumes that render_block_size is ALWAYS greater than max_display
    var last_child = cn.childNodes[this.max_display - 1];
    var height = last_child.offsetTop + last_child.offsetHeight;
    this.dropdown.style.height = height + 'px';
    var selected = this.get_suggestion_node(this.selectedindex);
    if (selected) {
      var scrollTop = this.dropdown.scrollTop;
      if (selected.offsetTop < scrollTop) {
        this.dropdown.scrollTop = selected.offsetTop;
      } else if (selected.offsetTop + selected.offsetHeight > height + scrollTop) {
        this.dropdown.scrollTop = selected.offsetTop + selected.offsetHeight - height;
      }
    }
    // Safari 3 has REALLY weird behavior with scrollbars, but overflowY seems to work almost cross-browser
    // I wanted to leave that note here, because at first glance style.overflowY seems less than optimal.
    // Also, Safari 2 doesn't respect style.overflow='auto' in Javascript, it seems (I could be wrong, I didn't
    // test this too much).
    // If you make any changes to overflow-related code in typeaheadpro, be sure to test well in Safari 2 and
    // Safari 3 -- this code is unreasonably sensitive.
    if (!typeaheadpro.should_use_overflow) {
      this.dropdown.style.overflowY = 'scroll';
      this.dropdown.style.overflowX = 'hidden';
    }
  } else {
    this.dropdown.style.height = 'auto';
    if (!typeaheadpro.should_use_overflow) {
      this.dropdown.style.overflowY = 'hidden';
    }
  }
}

// searches the local cache for the text
typeaheadpro.prototype.search_cache = function(text) {
  return this.cache[typeahead_source.flatten_string(text)];
}

// adds a value to the local cache
typeaheadpro.prototype.add_cache = function(text, results) {
  if (this.source.cache_results) {
    this.cache[typeahead_source.flatten_string(text)] = results;
  }
}

// called by source when it's done loading
typeaheadpro.prototype.update_status = function(status) {
  this.status = status;
  this.dirty_results();
}

// sets the class on the textbox while maintaining ones this object didn't fool around with
typeaheadpro.prototype.set_class = function(name) {
  this.obj.className = (this.obj.className.replace(/typeahead_[^\s]+/g, '') + ' ' + name).replace(/ {2,}/g, ' ');
}

// dirties the current results... fetches new results if need be
typeaheadpro.prototype.dirty_results = function() {

  if (!this.enumerate && trim(this.get_value()) == '') {
    this.results_text = '';
    this.set_message(this.source.gen_placeholder());
    this.suggestions = [];
    this.selectedindex = -1;
    return;
  } else if (this.results_text == typeahead_source.flatten_string(this.get_value())) {
    return; // just kidding! don't dirty!
  } else if (this.status == typeaheadpro.STATUS_BLOCK_ON_SOURCE_BOOTSTRAP) {
    this.set_message(this.source.gen_loading());
    return;
  }

  var time = (new Date).getTime();
  var updated = false;
  if (this.last_search <= (time - this.source.search_limit) && this.status == typeaheadpro.STATUS_IDLE) { // ready
    updated = this.perform_search();
  } else {
    if (this.status == typeaheadpro.STATUS_IDLE) {
      if (!this.search_timeout) {
        this.search_timeout = setTimeout(function() {
          this.search_timeout = false;
          if (this.status == typeaheadpro.STATUS_IDLE) {
            this.dirty_results();
          }
        }.bind(this), this.source.search_limit - (time - this.last_search));
      }
    }
  }

  // generate fake results from the last known results
  if (this.source.allow_fake_results && this.real_suggestions && !updated) {
    var ttext = typeahead_source.tokenize(this.get_value()).sort(typeahead_source._sort);
    var fake_results = [];
    for (var i = 0; i < this.real_suggestions.length; i++) {
      if (typeahead_source.check_match(ttext, this.real_suggestions[i].t + ' ' + this.real_suggestions[i].n)) {
        fake_results.push(this.real_suggestions[i]);
      }
    }
    if (fake_results.length) {
      this.found_suggestions(fake_results, this.get_value(), true);
    } else {
      this.selectedindex = -1;
      this.set_message(this.source.gen_loading());
    }
  }
}

// runs a search for the current search text
typeaheadpro.prototype.perform_search = function() {

  if (this.get_value() == this.results_text) {
    return true;
  }

  var results;
  if ((results = this.search_cache(this.get_value())) === undefined && // local cache
      !(results = this.source.search_value(this.get_value()))) {       // if this isn't going to return instantly
    this.status = typeaheadpro.STATUS_WAITING_ON_SOURCE;
    this.last_search = (new Date).getTime();
    return false;
  }
  this.found_suggestions(results, this.get_value(), false);
  return true;
}

// sets a message for the results
typeaheadpro.prototype.set_message = function(text) {
  this.clear_render_timeouts();
  if (text) {
    this.list.innerHTML = '<div class="typeahead_message">' + text + '</div>';
    this.reset_iframe();
  } else {
    this.hide();
  }
  this.recalc_scroll();
}

// moves the iframe to where it needs to be
typeaheadpro.prototype.reset_iframe = function() {
  if (!typeaheadpro.should_use_iframe) { return }
  if (this.should_use_absolute) {
    typeaheadpro.iframe.style.top = this.dropdown.style.top;
    typeaheadpro.iframe.style.left = this.dropdown.style.left;
  } else {
    typeaheadpro.iframe.style.top = elementY(this.dropdown)+'px';
    typeaheadpro.iframe.style.left = elementX(this.dropdown)+'px';
  }
  typeaheadpro.iframe.style.width = this.dropdown.offsetWidth+'px';
  typeaheadpro.iframe.style.height = this.dropdown.offsetHeight+'px';
  typeaheadpro.iframe.style.display = '';
}

// advances the form to the next available input
typeaheadpro.prototype.advance_focus = function() {
  var inputs=this.obj.form ? get_all_form_inputs(this.obj.form) : get_all_form_inputs();
  var next_inputs = false;
  for (var i=0; i<inputs.length; i++) {
    if (next_inputs) {
      if (inputs[i].type != 'hidden' && inputs[i].tabIndex != -1 && inputs[i].offsetParent) {
        next_inputs.push(inputs[i]);
      }
    } else if (inputs[i] == this.obj) {
      next_inputs = [];
    }
  }

  // omg this is so retarded. if you have an onblur event that destroys itself,
  // focus() gets all confused and just loses focus. so we do this with nested
  // timeouts to make damn sure the next element got focus
  setTimeout(function() {
    for (var i = 0; i < this.length; i++) {
      try {
        if (this[i].offsetParent) {
          this[i].focus();
          setTimeout(function() {
            try {
              this.focus();
            } catch(e) {}
          }.bind(this[i]), 0);
          return;
        }
      } catch(e) {}
    }
  }.bind(next_inputs ? next_inputs : []), 0);
}

// clears out the placeholder if need be
typeaheadpro.prototype.clear_placeholder = function() {
  if (this.obj.className.indexOf('typeahead_placeholder')!=-1) {
    this.set_value('');
    this.set_class('');
  }
}

// clear the input
typeaheadpro.prototype.clear = function() {
  this.set_value('');
  this.set_class('');
  this.selectedindex = -1;
  this.enumerate = false;
  this.dirty_results();
}

// hide the suggestions
typeaheadpro.prototype.hide = function() {
  if (this.stop_hiding) {
    return;
  }
  this.visible = false;
  if (this.should_use_absolute) {
    this.dropdown.style.display = 'none';
  } else {
    this.dropdown.style.visibility = 'hidden';
  }
  this.clear_render_timeouts();
  if (typeaheadpro.should_use_iframe) {
    typeaheadpro.iframe.style.display='none';
  }
}

// show the suggestions
typeaheadpro.prototype.show = function() {
  this.visible = true;
  if (this.focused) {
    if (this.should_use_absolute) {
      this.dropdown.style.top = elementY(this.anchor) + this.anchor.offsetHeight + 'px';
      this.dropdown.style.left = elementX(this.anchor) + 'px';
    }
    this.dropdown.style.width = (this.anchor.offsetWidth-2) + 'px'; // assumes a border of 2px
    this.dropdown.style[this.should_use_absolute ? 'display' : 'visibility'] = '';
    if (typeaheadpro.should_use_iframe) {
      typeaheadpro.iframe.style.display='';
      this.reset_iframe();
    }
  }
}

// toggle the list that shows up when you click the typeahead_icon
typeaheadpro.prototype.toggle_icon_list = function(no_focus) {
  if (this.showing_icon_list) {
    this.showing_icon_list = false;
    this.source.showing_icon_list = false;
    // hide
    if (!no_focus) {
      this.focus();
    }
    remove_css_class_name(this.typeahead_icon, 'on_selected');
    this.want_icon_list = false;
    this.showing_icon_list = false;
    this.stop_suggestion_select = true;
    if (this.obj) {
      this.dirty_results();
    }
  } else {
    this.source.showing_icon_list = true;
    this.old_typeahead_value = this.obj.value;
    this.stop_suggestion_select = true;
    this.want_icon_list = true;
    this.dirty_results();
    this.focus();
    add_css_class_name(this.typeahead_icon, 'on_selected');
    this.show();
    this.set_suggestion(-1);
    this.showing_icon_list = true;
  }
  // hacky because of IE event stuff
  setTimeout(function() { this.stop_hiding = false;}.bind(this), 100)
}

// focus the input
typeaheadpro.prototype.focus = function() {
    this.obj.focus();
}

typeaheadpro.prototype.blur = function() {
  this.obj.blur();
}

// kills an input's typeahead obj (if there is one)
/* static */ typeaheadpro.kill_typeahead = function(obj) {
  if (obj.typeahead) {
    if (!this.should_use_absolute && !this.anchor_block) {
      obj.parentNode.removeChild(obj.nextSibling); // <br />
    }
    obj.parentNode.removeChild(obj.nextSibling); // <div>
    if (obj.typeahead.source) {
      obj.typeahead.source =
      obj.typeahead.source.owner = null;
    }
    obj.onfocus =
    obj.onblur =
    obj.onkeypress =
    obj.onkeyup =
    obj.onkeydown =
    obj.typeahead = null;
  }
}

//
// the tokenizer, used on the compose pages
// =======================================================================================
function tokenizer(obj, typeahead_source, nofocus, max_selections, properties) {
  // hacks
  if (ua.safari() < 500) {
    tokenizer.valid_arrow_count = 0;
    tokenizer.valid_arrow_event = function() { return tokenizer.valid_arrow_count++ % 2 == 0 };
  } else {
    tokenizer.valid_arrow_event = function() { return true };
  }

  // setup the dom elements
  this.obj = obj;
  this.obj.tokenizer = this;
  this.typeahead_source = typeahead_source;

  while (!/\btokenizer\b/.test(this.obj.className)) {
    this.obj = this.obj.parentNode;
  }
  this.tab_stop = this.obj.getElementsByTagName('input')[0];

  // event hooks
  this.inputs = [];
  this.obj.onmousedown = function(event) {return this._onmousedown(event ? event : window.event)}.bind(this);
  this.tab_stop.onfocus = function(event) {return this._onfocus(event ? event : window.event)}.bind(this);
  this.tab_stop.onblur = function(event) {return this.tab_stop_onblur(event ? event : window.event)}.bind(this);
  this.tab_stop.onkeydown = function(event) {return this.tab_stop_onkeydown(event ? event : window.event)}.bind(this);
  if (!nofocus && elementY(this.obj) > 0 && this.obj.offsetWidth) {
    this._onfocus();
  }
  this.max_selections = max_selections;

  // copy over supplied parameters
  copy_properties(this, properties || {});
  // Store this list for tokenizer_input creation later
  this.properties = properties;
}

/* static */tokenizer.is_empty = function(obj) {
  if (has_css_class_name(obj, 'tokenizer_locked')) {
    return obj.getElementsByTagName('input').length == 0;
  } else {
    return (!obj.tokenizer || obj.tokenizer.count_names() == 0);
  }
}

tokenizer.prototype.get_token_values = function() {
  var r = [];
  var inputs = this.obj.getElementsByTagName('input');
  for (var i = 0; i < inputs.length; ++i) {
    if (inputs[i].name && inputs[i].value) {
      r.push(inputs[i].value);
    }
  }
  return r;
}

tokenizer.prototype.get_token_strings = function() {
  var r = [];
  var tokens = this.obj.getElementsByTagName('a');
  for (var i = 0; i < tokens.length; ++i) {
    if (typeof tokens[i].token != 'undefined') {
      r.push(tokens[i].token.text);
    }
  }
  return r;
}

tokenizer.prototype.clear = function() {
  var tokens = this.obj.getElementsByTagName('a');
  for (var i = tokens.length - 1; i >= 0; --i) {
    if (typeof tokens[i].token != 'undefined') {
      tokens[i].token.remove();
    }
  }
}

tokenizer.prototype._onmousedown = function(event) {
  // onmousedown is really onfocus, duh
  if (this.onfocus) {
    this.onfocus();
  }
  setTimeout(function() {
    if (!this.inputs.length) {
      if (this.max_selections > this.count_names()) {
        new tokenizer_input(this);
      } else {
        var tokens = this.obj.getElementsByTagName('a');
        for (var i=tokens.length-1; i>=0; i--) {
          if (typeof tokens[i].token != 'undefined') {
            tokens[i].token.select();
            break;
          }
        }
      }
    } else {
      this.inputs[0].focus();
    }
  }.bind(this),0);

  event ? event.cancelBubble = true : false;
  return false;
}

tokenizer.prototype._onfocus = function(event) {
  if (this.tab_stop_ignore_focus) {
    this.tab_stop_ignore_focus = false;
    return;
  }


  this._onmousedown();
}

tokenizer.prototype.tab_stop_onblur = function(event) {
  this.selected_token ? this.selected_token.deselect() : false;
}

tokenizer.prototype.tab_stop_onkeydown = function(event) {
  if (!event.keyCode || !this.selected_token) { return; }

  switch (event.keyCode) {
    case 8: // backspace
    case 46: // delete
      var tok = this.selected_token;
      var prev = tok.element.previousSibling;
      if (prev && prev.input) {
        prev.input.element.focus();
      } else {
        new tokenizer_input(this, tok.element);
      }
      tok.remove();
      return false;

    case 37: // left
      if (!tokenizer.valid_arrow_event()) { break; }
      var tok = this.selected_token;
      var prev = tok.element.previousSibling;
      if (prev && prev.input) {
        prev.input.element.focus();
      } else if (this.max_selections > this.count_names()) {
        new tokenizer_input(this, tok.element);
      } else {
        return false;
      }
      tok.deselect();
      return false;

    case 39: // right
      if (!tokenizer.valid_arrow_event()) { break; }
      var tok = this.selected_token;
      var next = tok.element.nextSibling;
      if (next && next.input) {
        next.input.focus();
      } else if (this.max_selections > this.count_names()) {
        new tokenizer_input(this, tok.element.nextSibling);
      } else {
        return false;
      }
      tok.deselect();
      return false;
  }

}// returns the number of unique people in this tokenizer
tokenizer.prototype.count_names = function(plus) {
  var inputs = this.obj.getElementsByTagName('input');
  var uniq = {};
  var count = 0;
  for (var i=0; i < inputs.length; i++) {
    if (inputs[i].type == 'hidden' &&
        !uniq[inputs[i].value]) {
      uniq[inputs[i].value] = true;
      ++count;
    }
  }
  if (plus) {
    for (var j = 0; j < plus.length; j++) {
      if (!uniq[plus[j]]) {
        uniq[plus[j]] = true;
        ++count;
      }
    }
  }
  return count;
}

// disables and locks the tokenizer. there's currently no reanble... so be careful :)
tokenizer.prototype.disable = function() {
  this.tab_stop.parentNode.removeChild(this.tab_stop);
  this.obj.className += ' tokenizer_locked';
}

  function tokenizer_input(tokenizer, caret) {
  if (!tokenizer_input.hacks) {
    // safari doesn't let you style input boxes much, so this is a hack with negative margins to hide their stupid styling
    tokenizer_input.should_use_borderless_hack = ua.safari();
    // internet explorer and opera are really silly about floats, which is unfortunate because safari and firefox behave differently.
    // we can do the resizing of these input fields almost automatically with CSS, but since the float behavior is wacky we need to
    // set style.width on every keystroke. we special case it out here so other browsers don't have to deal with the speed decrease.
    // this turns into a pretty decent speed boost for safari.
    tokenizer_input.should_use_shadow_hack = ua.ie() || ua.opera();
    tokenizer_input.hacks = true;
  }
  this.tokenizer = tokenizer;

  // Build obj... this is our <input> that the user types into
  this.obj = document.createElement('input');
  this.obj.input = this;
  this.obj.tabIndex = -1;
  this.obj.size = 1;
  this.obj.onmousedown = function(event) {(event ? event : window.event).cancelBubble=true}.bind(this);

  // Build the shadow. This is a hidden span element that streches out the parent div based on the input's contents
  this.shadow = document.createElement('span');
  this.shadow.className = 'tokenizer_input_shadow';

  // The parent for the whole thing
  this.element = document.createElement('div');
  this.element.className = 'tokenizer_input' + (tokenizer_input.should_use_borderless_hack ? ' tokenizer_input_borderless' : '');
  this.element.appendChild(document.createElement('div'));
  this.element.firstChild.appendChild(this.obj);
  (tokenizer_input.should_use_shadow_hack ? document.body : this.element.firstChild).appendChild(this.shadow);
  caret ? tokenizer.obj.insertBefore(this.element, caret) : tokenizer.obj.appendChild(this.element);
  this.tokenizer.tab_stop.disabled = true;
  this.update_shadow();
  this.update_shadow = this.update_shadow.bind(this); // always bind to this instance
  this.tokenizer.inputs.push(this);

  this.parent.construct(this, this.obj, this.tokenizer.typeahead_source);
  if (this.focused) {
    this.focus();
    this.obj.select();
  }

  // Copy the tokenizer properties into this object
  copy_properties(this, tokenizer.properties || {});

  // auto-resize even for copy/pasted email addresses
  setInterval(this.update_shadow.bind(this), 100);
}
tokenizer_input.extend(typeaheadpro);
tokenizer_input.prototype.gen_nomatch =
tokenizer_input.prototype.gen_loading =
tokenizer_input.prototype.gen_placeholder =
tokenizer_input.prototype.gen_noinput = '';
tokenizer_input.prototype.max_display = 8;

tokenizer_input.prototype.setup_anchor = function() {
  return this.tokenizer.obj;
}

tokenizer_input.prototype.update_shadow = function() {
  try {
    var val = this.obj.value;
  } catch(e) { return }; // this might be called after the input is dead
  if (this.shadow_input != val) {
    this.shadow.innerHTML = htmlspecialchars((this.shadow_input = val) + '^_^');
    if (tokenizer_input.should_use_shadow_hack) {
      this.obj.style.width = this.shadow.offsetWidth+'px';
      this.obj.value = val;
    }
  }
}

tokenizer_input.prototype._onblur = function() {
  if (this.parent._onblur() === false) {
    return false;
  }
  if (this.changed && !this.interactive) {
    this.dirty_results();
    this.changed = false;
    return;
  }
  if (this.changed || this.interactive) {
    this.select_suggestion(this.selectedindex);
  }
  setTimeout(function() {this.disabled=false}.bind(this.tokenizer.tab_stop), 1000);

  // Use a callback here to destroy ourselves.  Otherwise, on Firefox, the caret
  // won't end up where the user clicked.
  tokenizerToDestroy = this;
  setTimeout(function() {tokenizerToDestroy.destroy();}, 0);
}

tokenizer_input.prototype._onfocus = function() {
  this.tokenizer.tab_stop.disabled = true;
  this.parent._onfocus();
  return true;
}

tokenizer_input.prototype._onkeydown = function(event) {
  switch (event.keyCode) {
    case 13: // enter
      break;

    case 37: // left
    case 8: // backspace
      if (this.get_selection_start() !=0 || this.obj.value != '') {
        break;
      }
      var prev = this.element.previousSibling;
      if (prev && prev.token) {
        setTimeout(prev.token.select.bind(prev.token), 0);
      }
      break;

    case 39: // right
    case 46: // delete
      if (this.get_selection_start() != this.obj.value.length) {
        break;
      }
      var next = this.element.nextSibling;
      if (next && next.token) {
        setTimeout(next.token.select.bind(next.token), 0);
      }
      break;

    case 188: // comma
      this._onkeydown({keyCode:13});
      return false;

    case 9: // tab
      if (this.obj.value) {
        this.advance_focus();
        this._onkeydown({keyCode:13});
        return false;
      } else if (!event.shiftKey) {
        this.advance_focus();
        this.parent._onkeydown(event);
        return false;
      }
      break;
  }

  return this.parent._onkeydown(event);
}

tokenizer_input.prototype._onkeypress = function(event) {
  switch (event.keyCode) {
    case 9: // tab
      return false;
  }
  setTimeout(this.update_shadow, 0);
  return this.parent._onkeypress(event);
}

// override this to not fire if it's already entered
tokenizer_input.prototype.select_suggestion = function(index) {
  if (this.suggestions && index >= 0 && this.suggestions.length > index) {
    var inputs = this.tokenizer.obj.getElementsByTagName('input');
    var id = this.suggestions[index].i;
    for (i = 0; i < inputs.length; i++) {
      if (inputs[i].name == 'ids[]' && inputs[i].value == id) {
        return false;
      }
    }
  }
  return this.parent.select_suggestion(index);
}

// move this to base.js if needed
tokenizer_input.prototype.get_selection_start = function() {
  if (this.obj.selectionStart != undefined) {
    return this.obj.selectionStart;
  } else {
    return Math.abs(document.selection.createRange().moveStart('character', -1024));
  }
}

tokenizer_input.prototype.onselect = function(obj) {
  if (obj) {
    var inputs = this.tokenizer.obj.getElementsByTagName('input');
    for (i=0; i<inputs.length; i++) {
      if (inputs[i].name == 'ids[]' && inputs[i].value == obj.i) {
        return false;
      }
    }
    new token(obj, this.tokenizer, this.element);

    if (this.tokenizer.max_selections > this.tokenizer.count_names()) {
      this.clear();
    } else {
      this.destroy();
      this.hide = function() {}; // workaround because this gets called later on a destroy'd element
      return false;
    }
  }

  if (obj) {
    this.tokenizer._ontokenadded(obj);
  }

  this.tokenizer.typeahead_source.onselect_not_found.call(this);
  return false;
}

// event handler when the user adds a token
tokenizer.prototype._ontokenadded = function(obj) {
  if (this.ontokenadded) {
    this.ontokenadded.call(this, obj);
  }
}


// event handler when the user removes a token
tokenizer.prototype._ontokenremoved = function(obj) {
  if (this.ontokenremoved) {
    this.ontokenremoved.call(this, obj);
  }
}

// event handler when the user tries to add a token that isn't in the index
tokenizer.prototype._ontokennotfound = function(text) {
  if (this.ontokennotfound) {
    this.ontokennotfound.call(this, text);
  }
}

tokenizer_input.prototype._onsubmit = function() {
  return false;
}

// uneeded since we don't use submits with this guy
tokenizer_input.prototype.capture_submit = function() {
  return false;
}

tokenizer_input.prototype.clear = function() {
  this.parent.clear();
  this.update_shadow();
}

tokenizer_input.prototype.destroy = function() {
  if (tokenizer_input.should_use_shadow_hack) {
    this.shadow.parentNode.removeChild(this.shadow);
  }
  this.element.parentNode.removeChild(this.element);

  this.element = null;

  var index = this.tokenizer.inputs.indexOf(this);
  if (index != -1) {
    this.tokenizer.inputs.splice(index, 1);
  }
  this.tokenizer =
  this.element =
  this.shadow = null;

  this.parent.destroy();
  return null;
}


function token(obj, tokenizer, caret) {
  if (obj.is && (tokenizer.count_names(obj.is) > tokenizer.max_selections)) {
    (new contextual_dialog).set_context(tokenizer.obj).show_prompt(tx('ta12'), tx('ta13')).fade_out(500, 1500);
    return null;
  }
  this.tokenizer = tokenizer;
  this.element = document.createElement('a');
  this.element.className = 'token';
  this.element.href = '#';
  this.element.tabIndex = -1;
  this.element.onclick = function(event) {return this._onclick(event ? event : window.event)}.bind(this);
  this.element.onmousedown = function(event) {(event ? event : window.event).cancelBubble = true; return false};
  this.render_obj(obj);
  this.obj = obj;
  this.element.token = this;
  caret ? this.tokenizer.obj.insertBefore(this.element, caret) : this.tokenizer.obj.appendChild(this.element);
}

token.prototype.render_obj = function(obj) {
  var inputs = '';
  // note: unless they give us "np", add fb_protected="true" as an attribute so
  // we can verify on platform pages that these are actually typeahead
  // selectors.  not protecting with "np" is necessary for the case where the
  // app is prefilling the tokens so we don't want to treat them as valid
  // request recipients (for fb:request-forms).
  if (obj.np) {
    var fb_protected='';
  } else {
    var fb_protected='fb_protected="true" ';
  }
  if (obj.e) {
    inputs = ['<input type="hidden" ', fb_protected, 'name="emails[]" value="', obj.e, '" />'].join('');
  } else if (obj.i) {
    inputs = ['<input type="hidden" ', fb_protected, 'name="', this.tokenizer.obj.id, '[]" value="', obj.i, '" />'].join('');
  } else if (obj.is) {
    for (var i = 0, il = obj.is.length; i < il; i++) {
      inputs += ['<input type="hidden" ', fb_protected, 'name="', this.tokenizer.obj.id, '[]" value="', obj.is[i], '" />'].join('');
    }
    this.explodable = true;
    this.n = obj.n;
  }
  this.text = obj.t;

  this.element.innerHTML = ['<span><span><span><span>',
                            inputs,
                            htmlspecialchars(obj.t),
                            '<span onclick="this.parentNode.parentNode.parentNode.parentNode.parentNode.token.remove(true); event.cancelBubble=true; return false;" ',
                                  'onmouseover="this.className=\'x_hover\'" onmouseout="this.className=\'x\'" class="x">&nbsp;</span>',
                            '</span></span></span></span>'].join('');
}

token.prototype._onclick = function(event) {
  // Detect and process doubleclick on explodable things
  var this_select_time = (new Date()).getTime();
  if (this.explodable &&
      this.tokenizer.last_select_time &&
      (this_select_time - this.tokenizer.last_select_time < 1400)) {

    // Grab the list of things to add
    var to_add = this.n;
    this.remove();

    // Figure out what is already present
    var inputs = this.tokenizer.obj.getElementsByTagName('input');
    var already_ids = {};
    for (var i = 0; i < inputs.length; ++i) {
      if (inputs[i].name == 'ids[]') {
        already_ids[inputs[i].value] = true;
      }
    }
    for (var id in to_add) {
      if (!already_ids[id]) {
        new token({'t' : to_add[id], 'i' : id}, this.tokenizer);
      }
    }
  } else {
    this.select();
  }

  this.tokenizer.last_select_time = this_select_time;
  event.cancelBubble = true;
  return false;
}

token.prototype.select = function(again) {
  if (this.tokenizer.selected_token && !again) {
    this.tokenizer.selected_token.deselect();
  }
  this.element.className = trim(this.element.className.replace('token_selected', '')) + ' token_selected';
  this.tokenizer.tab_stop_ignore_focus = true;
  if (this.tokenizer.tab_stop.disabled) {
    this.tokenizer.tab_stop.disabled = false;
  }
  this.tokenizer.tab_stop.focus();
  this.tokenizer.selected_token = this;
  if (again !== true) {
    setTimeout(function() {this.select(true)}.bind(this), 0);
  } else {
    setTimeout(function() {this.tab_stop_ignore_focus = false}.bind(this.tokenizer), 0);
  }
}

token.prototype.remove = function(focus) {
  this.element.parentNode.removeChild(this.element);
  this.element.token = null;
  this.tokenizer.selected_token = null;
  if (focus) {
    this.tokenizer._onmousedown();
  }
  if (this.obj) {
    this.tokenizer._ontokenremoved(this.obj);
  }
}

token.prototype.deselect = function() {
  this.element.className = trim(this.element.className.replace('token_selected', ''));
  this.tokenizer.selected_token = null;
}


//
// typeahead source generic class
// =======================================================================================
function typeahead_source() {
}
typeahead_source.prototype.cache_results = false;      // may the owner cache results?
typeahead_source.prototype.enumerable = false;         // is it possible to get a full list of the options?
typeahead_source.prototype.allow_fake_results = false; // if the source is slow should typeaheadpro be allowed to generate fake data
                                                       //   to create the illusion of responsiveness?
typeahead_source.prototype.search_limit  = 10;         // how often can we run a query?

// basically a tokenized search
/* static */ typeahead_source.check_match = function(search, value) {
  value = typeahead_source.tokenize(value);
  for (var i = 0, il = search.length; i < il; i++) {
    if (search[i].length) { // do we want to count this piece as a search token?
      var found = false;
      for (var j = 0, jl = value.length; j < jl; j++) {
        if (value[j].length >= search[i].length && value[j].substring(0, search[i].length) == search[i]) {
          found = true;
          value[j]=''; // prevent this piece of the name from being matched again
          break;
        }
      }
      if (!found) {
        return false;
      }
    }
  }
  return true;
}

// takes a string and returns an array strings that should be used for searching
/* static */ typeahead_source.tokenize = function(text, capture, noflatten) {
  return (noflatten ? text : typeahead_source.flatten_string(text)).split(capture ? typeahead_source.normalizer_regex_capture : typeahead_source.normalizer_regex);
}
typeahead_source.normalizer_regex_str = '(?:(?:^| +)["\'.\\-]+ *)|(?: *[\'".\\-]+(?: +|$)|@| +)';
typeahead_source.normalizer_regex = new RegExp(typeahead_source.normalizer_regex_str, 'g');
typeahead_source.normalizer_regex_capture = new RegExp('('+typeahead_source.normalizer_regex_str+')', 'g');

// replaces accented characters with the non-accented version. also lower-case the strings.
/* static */ typeahead_source.flatten_string = function(text) {
  if (!typeahead_source.accents) {
    typeahead_source.accents = {
      a: /à|á|â|ã|ä|å/g,
      c: /ç/g,
      d: /ð/g,
      e: /è|é|ê|ë/g,
      i: /ì|í|î|ï/g,
      n: /ñ/g,
      o: /ø|ö|õ|ô|ó|ò/g,
      u: /ü|û|ú|ù/g,
      y: /ÿ|ý/g,
      ae: /æ/g,
      oe: /œ/g
    }
  }
  text = text.toLowerCase();
  for (var i in typeahead_source.accents) {
    text = text.replace(typeahead_source.accents[i], i);
  }
  return text;
}

// sets the owner (i.e. typeahead) of this source
typeahead_source.prototype.set_owner = function(obj) {
  this.owner = obj;
  if (this.is_ready) {
    this.owner.update_status(typeaheadpro.STATUS_IDLE);
  }
}

// this source is ready to search
typeahead_source.prototype.ready = function() {
  if (this.owner && !this.is_ready) {
    this.is_ready = true;
    this.owner.update_status(typeaheadpro.STATUS_IDLE);
  } else {
    this.is_ready = true;
  }
}

// highlights found text with searched text
/* static */ typeahead_source.highlight_found = function(result, search) {
  var html = [];
  resultv = typeahead_source.tokenize(result, true, true);
  result = typeahead_source.tokenize(result, true);
  search = typeahead_source.tokenize(search);
  search.sort(typeahead_source._sort); // do this to make sure the larger piece gets matched first
  for (var i = 0, il = resultv.length; i < il; i++) {
    var found = false;
    for (var j = 0, jl = search.length; j < jl; j++) {
      if (search[j] && result[i].lastIndexOf(search[j], 0) != -1) { // does this result[i] start with search[j]
        html.push('<em>', htmlspecialchars(resultv[i].substring(0, search[j].length)), '</em>', htmlspecialchars(resultv[i].substring(search[j].length, resultv[i].length)));
        found = true;
        break;
      }
    }
    if (!found) {
      html.push(htmlspecialchars(resultv[i]));
    }
  }

  return html.join('');
}

// helper function for sorting tokens
/* static */ typeahead_source._sort = function(a, b) {
  return b.length - a.length;
}

// returns error text for when nothing was found
typeahead_source.prototype.gen_nomatch = function() {
  return this.text_nomatch != null ? this.text_nomatch : tx('ta01');
}

// returns message in case the selector is still loading
typeahead_source.prototype.gen_loading = function() {
  return this.text_loading != null ? this.text_loading : tx('ta02');
}

// returns filler text for when the user hasn't typed anything in
typeahead_source.prototype.gen_placeholder = function() {
  return this.text_placeholder != null ? this.text_placeholder : tx('ta03');
}

// returns filler text for when the user hasn't typed anything in
typeahead_source.prototype.gen_noinput = function() {
  return this.text_noinput != null ? this.text_noinput : tx('ta03');
}

typeahead_source.prototype.onselect_not_found = function() {
  if (typeof this.tokenizer._ontokennotfound != 'undefined') {
    this.tokenizer._ontokennotfound(this.obj.value);
  }

  if (typeof this.tokenizer.onselect != 'undefined') {
    return this.tokenizer.onselect();
  }
}

//
// static source base class. use this if you have a set list of this to search for that can be handled totally on the client-side
// =======================================================================================
function static_source() {
  this.values = null;
  this.index = null;
  this.index_includes_hints = false;
  this.exclude_ids = {};
  this.parent.construct(this);
}
static_source.extend(typeahead_source);
static_source.prototype.enumerable = true;

// builds a sorted index for us to use in a binary search
static_source.prototype.build_index = function(no_defer) {
  var index = [];
  var values = this.values;
  var gen_id = values.length && typeof values[0].i == 'undefined'; // generate our own ids for these
  for (var i = 0, il = values.length; i < il; i++) {
    var tokens = typeahead_source.tokenize(values[i].t);
    for (var j = 0, jl = tokens.length; j < jl; j++) {
      index.push({t:tokens[j], o:values[i]});
    }
    // also include the sub-tag label in the index
    if (this.index_includes_hints && values[i].s) {
      var tokens = typeahead_source.tokenize(values[i].s);
      for (var j = 0, jl = tokens.length; j < jl; j++) {
        index.push({t:tokens[j], o:values[i]});
      }
    }


    if (gen_id) {
      values[i].i = i;
    }
  }

  // This can take some time, let's defer it
  var index_sort_and_ready = function () {
    index.sort(function(a,b) {return (a.t == b.t) ? 0 : (a.t < b.t ? -1 : 1)});
    this.index = index;
    this.ready();
  }.bind(this);
  if (no_defer) {
    index_sort_and_ready();
  } else {
    index_sort_and_ready.defer();
  }
}

// we want email addresses to always be displayed at the
// bottom of the list, to keep the friend selector
// relatively clean
static_source.prototype._sort_text_obj = function(a, b) {
  if (a.e && !b.e) {
    return 1;
  }
  if (!a.e && b.e) {
    return -1;
  }
  if (a.t == b.t) {
    return 0;
  }
  return a.t < b.t ? -1 : 1
}

// searches the values list for some text and returns those to the typeahead
static_source.prototype.search_value = function(text) {
  if (!this.is_ready) {
    return;
  }

  var results;
  if (text == '') {
    results = this.values;
  } else {
    var ttext = typeahead_source.tokenize(text).sort(typeahead_source._sort);
    var index = this.index;
    var lo = 0;
    var hi = this.index.length - 1;
    var p  = Math.floor(hi / 2);

    // first we go through and set our cursor to the start of the most restrictive match in the index
    while (lo <= hi) {
      if (index[p].t >= ttext[0]) {
        hi = p - 1;
      } else {
        lo = p + 1;
      }
      p = Math.floor(lo + ((hi-lo) / 2));
    }

    // now match the rest of the tokens
    // note: it would be nice if we could break this loop after we get search_limit results, but we can't.
    // since they're going to be in the order of the index, names will look scattered and unorganized to the
    // user. instead we just grab all the names that match, and then sort them later.
    var results = [];
    var stale_keys = {};
    var check_ignore = typeof _ignoreList != 'undefined';
    for (var i=lo; i<index.length && index[i].t.lastIndexOf(ttext[0], 0) != -1; i++) {
      var elem_id = index[i].o.flid ? index[i].o.flid : index[i].o.i;
      if (typeof stale_keys[elem_id] != 'undefined') {
        continue;
      } else {
        stale_keys[elem_id] = true;
      }
      if ((!check_ignore || !_ignoreList[elem_id])
          && !this.exclude_ids[elem_id]
          && (ttext.length == 1 || typeahead_source.check_match(ttext, index[i].o.t))) {
        results.push(index[i].o);
      }
    }
  }

  // sort and pull the top n results
  results.sort(this._sort_text_obj);
  if (this.owner.max_results) {
    results = results.slice(0, this.owner.max_results);
  }

  return results;
}

static_source.prototype.set_exclude_ids = function(ids) {
  this.exclude_ids = ids;
}

//
// friend source for typeaheads
// =======================================================================================
function friend_source(get_param) {
  this.parent.construct(this);

  if (friend_source.friends[get_param]) {
    this.values = friend_source.friends[get_param];
    this.index = friend_source.friends_index[get_param];
    this.ready();
  } else {
    new AsyncRequest()
      .setMethod('GET')
      .setReadOnly(true)
      .setURI('/ajax/typeahead_friends.php?' + get_param)
      .setHandler(function(response) {
                    friend_source.friends[get_param]
                      = this.values
                      = response.getPayload().friends;
                    this.build_index();
                    friend_source.friends_index[get_param] = this.index;
                  }.bind(this))
      .send();
  }
}
friend_source.extend(static_source);
friend_source.prototype.text_noinput =
friend_source.prototype.text_placeholder = tx('ta04');
friend_source.friends = {};
friend_source.friends_index = {};
friend_source.prototype.cache_results = true;

// generates html for this friend's typeahead
friend_source.prototype.gen_html = function(friend, highlight) {
  var text = friend.n;
  if (friend.n === false) {
    // empty friend list
    text = tx('ta16');
  } else if (typeof(friend.n) == "object") {
    var names = [];
    for (var k in friend.n) {
      names.push(friend.n[k]);
    }
    if (names.length > 3) {
      text = tx('ta15', {name1: names[0],
                         name2: names[1],
                         count: names.length - 2});
    } else if (names.length) {
      text = names.join(', ');
    } else {
      text = tx('ta16');
    }
  }
  return ['<div>', typeahead_source.highlight_found(friend.t, highlight), '</div><div><small>', text, '</small></div>'].join('');
}

// searches the friends list for some text and returns those to the typeahead
friend_source.prototype.search_value = function(text) {
  if (text == '\x5e\x5f\x5e') { // early sentinel value
    return [{t:text,n:'\x6b\x65\x6b\x65',i:10,it:'http://static.ak.facebook.com/pics/t_default.jpg'}];
  }
  return this.parent.search_value(text);
}

function friendlist_source(get_param) {
  this.parent.construct(this, get_param);
}
friendlist_source.extend(friend_source);

friendlist_source.prototype.friend_lists = false;
friendlist_source.prototype.text_placeholder = tx('ta18');

friendlist_source.prototype.return_friend_lists = function() {
  if (!this.friend_lists || (this.friend_lists && this.friend_lists.length == 0)) {
    this.friend_lists = [];
    var index = this.index;
    var results = [];
    var pushed = [];
    if (!index.length || !(index.length >= 1)) {
      return;
    }
    for (var i=0; i < index.length; i++) {
      if (index[i].o.flid && !pushed[index[i].o.flid]) {
        pushed[index[i].o.flid] = true;
        results.push(index[i].o);
      }
    }

    // sort results
    var results_sorted = results.sort(function(a, b) { if (a.t > b.t) return 1; else if (a.t < b.t) return -1; else return 0; });

    this.friend_lists = results_sorted;
  }
  return this.friend_lists;
}

friendlist_source.prototype.search_value = function(text) {
  if (text == '**FRIENDLISTS**') {
    return this.return_friend_lists();
  }
  return this.parent.search_value(text);
}

friendlist_source.prototype.gen_nomatch = function() {
  if (this.showing_icon_list) {
    return tx('ta17');
  } else {
    return this.parent.gen_nomatch();
  }
}

//
// friend and email source
// acts as a friend-finder, with additional ability to accept email addresses as well
// =======================================================================================
function friend_and_email_source(get_param) {
    get_param = get_param ? get_param + '&include_emails=1' : '';
    this.parent.construct(this, get_param);
}
friend_and_email_source.extend(friend_source);
friend_and_email_source.prototype.text_noinput =
friend_and_email_source.prototype.text_placeholder = tx('ta05');
friend_and_email_source.prototype.text_nomatch = tx('ta06');

friend_and_email_source.prototype.onselect_not_found = function() {

  // the loop catches the case where someone copy/pastes a bunch of emails in at once
  emails = this.results_text.split(/[,; ]/);

  for (var i = 0; i < emails.length; i++) {

    // only execute if it looks like an email. it's okay if this email_regex
    // doesn't handle every possible case .. if an invalid email is entered,
    // then the handling form will reject it on submission and display an error in the prefill
    var text = emails[i].replace(/^\s+|\s+$/g, '');
    var email_regex = /.*\@.*\.[a-z]+$/;

    if (!email_regex.test(text)) {
      continue;
    }

    var email_entry = {t:text, e:text};
    var new_token = new token(email_entry, this.tokenizer, this.element);

    // the ajax call is executed in the context of the token. this is necessary
    // because the tokenizer_input might be destroyed by the time the
    // call returns, so it needs something that will reliably be there

    var async_params = { email : text };
      new AsyncRequest()
        .setMethod('GET')
        .setReadOnly(true)
        .setURI('/ajax/typeahead_email.php')
        .setData(async_params)
        .setHandler(function(response) {
                      if (response.getPayload()) {
                        this.render_obj(response.getPayload().token);
                      }
                    }.bind(new_token))
        .send();
  }
  this.clear();
}

//
// network source for networks and stuff... when needed this should be further abstracted to ajax_source -> network_source
// =======================================================================================
function network_source(get_selected_type) {
  this.get_selected_type = get_selected_type;
  this.parent.construct(this);
  this.ready();
}
network_source.extend(typeahead_source);
network_source.prototype.cache_results = true;
network_source.prototype.search_limit = 200;   // how often can we run a query?
network_source.prototype.text_placeholder=network_source.prototype.text_noinput=tx('ta07');
network_source.prototype.base_uri='';
network_source.prototype.allow_fake_results = true;

// sends a query to look for the network. the owner won't call this until we respond with found_suggestions, so we don't have to implement any kind of throttling here.
network_source.prototype.search_value = function(text) {
  this.search_text = text;
  var async_params = { q : text };

  // type is settable by both 'get_selected_type' and 't'
  if ((type = typeof(this.get_selected_type)) != 'undefined') {
    async_params['t'] = (type != 'string')?JSON.encode(this.get_selected_type):this.get_selected_type;
  }
  if ((type = typeof(this.t)) != 'undefined') {
    async_params['t'] = (type != 'string')?JSON.encode(this.t):this.t;
  }

  // show_email and show_network_type can be switched on
  if (this.show_email) {
    async_params['show_email'] = 1;
  }
  if (this.show_network_type) {
    async_params['show_network_type'] = 1;
  }
  if (this.disable_school_status) {
    async_params['disable_school_status'] = 1;
  }

  new AsyncRequest()
  .setReadOnly(true)
  .setMethod('GET')
  .setURI('/ajax/typeahead_networks.php')
  .setData(async_params)
  .setHandler(function(response) {
                this.owner.found_suggestions(response.getPayload(), this.search_text);
              }.bind(this))
  .setErrorHandler(function(response) {
                     this.owner.found_suggestions(false, this.search_text);
                   }.bind(this))
  .send();
}

// generates html for this result
network_source.prototype.gen_html = function(result, highlight) {
  return ['<div>',
            typeahead_source.highlight_found(result.t, highlight),
          '</div><div><small>',
            typeahead_source.highlight_found(result.l, highlight),
          '</small></div>'].join('');
}

//
// custom source -- pass it an array of stuff and it'll autocomplete from the list
function custom_source(options) {
  this.parent.construct(this);

  //  If the caller passed an array of strings, convert them to canonical
  //  typeahead format: objects with a (t)oken and (i)ndex field.
  if (options.length && typeof(options[0]) == "string") {
    for (var ii = 0; ii < options.length; ii++) {
      options[ii] = {t: options[ii], i: options[ii]};
    }
  }

  this.values = options;
  this.build_index();
}
custom_source.extend(static_source);
custom_source.prototype.text_placeholder =
custom_source.prototype.text_noinput = false;

// generates html for this result
custom_source.prototype.gen_html = function(result, highlight) {
  var html = ['<div>', typeahead_source.highlight_found(result.t, highlight), '</div>'];
  if (result.s) {
    html.push('<div><small>', htmlspecialchars(result.s), '</small></div>');
  }
  return html.join('');
}

//
// concentration source, for college majors\minors. this one is kind of interesting because we will probably have more than one from the same college on the page at once.
// =======================================================================================
function concentration_source(get_network) {
  this.parent.construct(this, []);
  this.network=get_network;

  // perhaps we already have these concentrations in static...
  if (!concentration_source.networks) {
    concentration_source.networks = [];
  } else {
    for (var i = 0, il = concentration_source.networks.length; i < il; i++) {
      if (concentration_source.networks[i].n == this.network) {
        this.values = concentration_source.networks[i].v;
        this.index = concentration_source.networks[i].i;
        this.ready();
        return;
      }
    }
  }

  // couldn't find the concentrations, get them from ajax
  new AsyncRequest()
    .setURI('/ajax/typeahead_concentrations.php?n=' + this.network)
    .setHandler(function(response) {
      this.values = response.getPayload();
      this.build_index();
      concentration_source.networks.push({n:this.network, v:this.values, i:this.index});
      this.ready();
    }.bind(this))
    .send();
}
concentration_source.extend(custom_source);
concentration_source.prototype.noinput = false;
concentration_source.prototype.text_placeholder = tx('ta08');
concentration_source.prototype.allow_fake_results = true;


function language_source() {
  this.parent.construct(this, []);

  // perhaps we already have these languages in static...
  if (!language_source.languages) {
    language_source.languages = [];
  } else {
    for (var i = 0, il = language_source.languages.length; i < il; i++) {
      this.values = language_source.languages[i].v;
      this.index = language_source.languages[i].i;
      this.ready();
      return;
    }
  }

  // couldn't find the concentrations, get them from ajax
  new AsyncRequest()
    .setURI('/ajax/typeahead_languages.php')
    .setHandler(function(response) {
      this.values = response.getPayload();
      this.build_index();
      language_source.languages.push({v:this.values, i:this.index});
      this.ready();
    }.bind(this))
    .send();
}
language_source.extend(custom_source);
language_source.prototype.noinput = false;
language_source.prototype.text_placeholder = tx('ta14');
language_source.prototype.allow_fake_results = false;

//
// Targeting keyword source.
// =======================================================================================
function keyword_source(get_category) {
  this.parent.construct(this, []);
  this.category = get_category;

  if (!keyword_source.categories) {
    keyword_source.categories = [];
  } else {
    for (var i = 0, il = keyword_source.categories.length; i < il; i++) {
      if (keyword_source.categories[i].c == this.category) {
        this.values = keyword_source.categories[i].v;
        this.index = keyword_source.categories[i].i;
        this.ready();
        return;
      }
    }
  }

  new AsyncRequest()
    .setURI('/ajax/typeahead_keywords.php')
    .setData({ c : this.category })
    .setMethod('GET')
    .setReadOnly(true)
    .setHandler(function(response) {
                  this.values = response.getPayload();
                  this.build_index();
                  keyword_source.categories.push({c:this.category, v:this.values, i:this.index});
                  this.ready();
                }.bind(this))
    .send();
}
keyword_source.extend(custom_source);
keyword_source.prototype.noinput = false;
keyword_source.prototype.text_placeholder = tx('ta09');

//
// Targeting regions source
// =======================================================================================
function regions_source(get_iso2) {
  this.parent.construct(this, []);
  this.country = get_iso2;
  this.reload();
}
regions_source.extend(custom_source);
regions_source.prototype.noinput = false;
regions_source.prototype.text_placeholder = tx('ta10');
regions_source.prototype.reload = function() {
  new AsyncRequest()
  .setMethod('GET')
  .setReadOnly(true)
  .setURI('/ajax/typeahead_regions.php')
  .setData({c : this.country})
  .setHandler(function(response) {
                this.values = response.getPayload();
                this.build_index();
                this.ready();
              }.bind(this))
  .send();
}

//
// Time selector for date selector pro
// To be re-written to not use ajax hopefully
// =======================================================================================
function time_source() {
  this.status=0;
  this.parent.construct(this);
}
time_source.extend(typeahead_source);
time_source.prototype.cache_results = true;
time_source.prototype.text_placeholder=time_source.prototype.text_noinput=tx('ta11');
time_source.prototype.base_uri='';

// sends a query to look for the network. the owner won't call this until we respond with found_suggestions, so we don't have to implement any kind of throttling here.
time_source.prototype.search_value=function(text) {
  this.search_text=text;
  var async_params = { q : text };
  new AsyncRequest()
  .setURI('/ajax/typeahead_time.php')
  .setMethod('GET')
  .setReadOnly(true)
  .setData(async_params)
  .setHandler(function(response) {
                this.owner.found_suggestions(response.getPayload(), this.search_text);
              }.bind(this))
  .setErrorHandler(function(response) {
                     this.owner.found_suggestions(false, this.search_text);
                   }.bind(this))
  .send();
}

// generates html for this result
time_source.prototype.gen_html=function(result, highlight) {
  return ['<div>', typeahead_source.highlight_found(result.t, highlight), '</div>'].join('');
}

function dynamic_custom_source(async_url) {
  this.async_url = async_url;
  this.parent.construct(this);
}
dynamic_custom_source.extend(typeahead_source);
dynamic_custom_source.cache_results = true;

dynamic_custom_source.prototype.search_value = function(text) {
  this.search_text = text;
  var async_params = { q : text };
  var r = new AsyncRequest()
    .setURI(this.async_url)
    .setData(async_params)
    .setHandler(bind(this, function(r) {
      this.owner.found_suggestions(r.getPayload(), this.search_text, false);
    }))
    .setErrorHandler(bind(this, function(r) {
      this.owner.found_suggestions(false, this.search_text, false);
    }))
    .setReadOnly(true)
    .send()
}

dynamic_custom_source.prototype.gen_html=function(result, highlight) {
  var html = ['<div>', this.highlight_found(result.t, highlight), '</div>'];
  if (result.s) {
    html.push('<div class="sub_result"><small>', result.s, '</small></div>');
  }

  return html.join('');
}

dynamic_custom_source.prototype.highlight_found = function(result, search) {
  return typeahead_source.highlight_found(result, search);
}


// Ad targting cluster source
//=================================================
function ad_targeting_cluster_source(act) {
  this.parent.construct(this, []);

  // See if clusters are already cached in this browser instance
  if (!ad_targeting_cluster_source.clusters) {
    ad_targeting_cluster_source.clusters = [];
  } else {
    for (var i = 0, il = ad_targeting_cluster_source.clusters.length; i < il; i++) {
      this.values = ad_targeting_cluster_source.clusters[i].v;
      this.index  = ad_targeting_cluster_source.clusters[i].i;
      this.ready();
      return;
    }
  }

  // Couldn't find the clusters, get them from ajax
  new AsyncRequest()
    .setURI('/ads/ajax/typeahead_clusters.php')
    .setData({'act' : act})
    .setHandler(function(response) {
        this.values = response.getPayload();
        this.build_index();
        ad_targeting_cluster_source.clusters.push({v:this.values, i:this.index});
        this.ready();
        }.bind(this))
  .send();
}

ad_targeting_cluster_source.extend(custom_source);



  /**************  dialogpro.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides pop-dialog
 *  @requires ua function-extensions
 *  @deprecated
 */

//
//
//  * * * * * * * * * * *
// * D E P R E C A T E D *
//  * * * * * * * * * * *
//
//  The new hotness is the Dialog class.  It's available at:
//
//     html/js/lib/ui/dialog.js
//
//  If you find that there's some reason you need dialogpro.js, and that
//  the Dialog class does not fit your needs, please let jrosenstein know
//  (or add what you need to the class :-).
//
//

//
// generic dialog class, does very little on its own
function generic_dialog(className, modal) {
  this.className      = className;
  this.content        = null;
  this.obj            = null;
  this.popup          = null;
  this.overlay        = null;
  this.modal          = null;
  this.iframe         = null;
  this.hidden_objects = [];
  if (modal == true) {
    this.modal = true;
  }
}
generic_dialog.dialog_stack = null;

generic_dialog.prototype.setClassName = function(className) {
  this.className = className;
};

generic_dialog.hide_all = function() {
  if (generic_dialog.dialog_stack !== null) {
    var stack = generic_dialog.dialog_stack.clone();
    generic_dialog.dialog_stack = null;

    for (var i = stack.length - 1; i >= 0; i--) {
      stack[i].hide();
    }
  }
};

generic_dialog.prototype.should_hide_objects = !ua.windows();
generic_dialog.prototype.should_use_iframe = ua.ie() < 7 || (ua.osx() && ua.firefox());

// shows a dialog with raw html
generic_dialog.prototype.show_dialog=function(html) {
  if (generic_dialog.dialog_stack === null) {
    // This is the first dialog we're showing on this 'page'.  (We may have
    // on a previous full-page Quickling load.)  This is a good time to
    // register a handler to make sure that all dialogs get hiddenwhen the
    // user leaves the page.
    onunloadRegister(generic_dialog.hide_all, true /* respect Quickling events */);
  }

  if (!this.obj) {
    this.build_dialog();
  }
  set_inner_html(this.content, html);

  // if we need to hide objects behind this, we need to check back after images are loaded
  var imgs = this.content.getElementsByTagName('img');
  for (var i=0; i<imgs.length; i++) {
    imgs[i].onload = chain(imgs[i].onload, this.hide_objects.bind(this));
  }
  this.show();

  // Focus the first textbox or textarea in the dialog, if any
  this.focus_first_textbox_or_button();
  this.on_show_callback && this.on_show_callback();

  return this;
}

// sets the callback for after the dialog is loaded and displayed
generic_dialog.prototype.set_callback = function(callback) {
  this.on_show_callback = callback;
  return this;
}

generic_dialog.prototype.focus_first_textbox_or_button = function() {
  /**
   * Focuses the node if it's a textbox and returns false to indicate that DOM traversal
   * should cease. Otherwise, does nothing and returns true
   */
  var INPUT_TYPES = { 'text': 1, 'button': 1, 'submit': 1 };
  function focus_textbox(node) {
    var is_textbox =
      (node.tagName == "INPUT" && INPUT_TYPES[node.type.toLowerCase()]) ||
      (node.tagName == "TEXTAREA");
    if (is_textbox) {
      try {
        if (elementY(node) > 0 && elementX(node) > 0) {
          node.focus();
          return false;
        }
      } catch(e) {};
    }
    return true;
  }
  iterTraverseDom(this.content, focus_textbox)
}

generic_dialog.prototype.set_top=function(top) {
  return this;
}

generic_dialog.prototype.make_modal=function() {
  if (this.modal) {
    return;
  }
  this.modal = true;
  // If the browser is IE7, then making a dialog modal means
  // adding an iframe.
  if (ua.ie() == 7) {
    this.build_iframe();
  }
  this.build_overlay();
  this.reset_iframe();
}

generic_dialog.prototype.show_loading=function(loading_html) {
  if (!loading_html) {
    loading_html = tx('sh:loading');
  }
  return this.show_dialog('<div class="dialog_loading">'+loading_html+'</div>');
}

generic_dialog.prototype.show_ajax_dialog_custom_loader=function(html, src, post_vars) {
    if (html) {
     this.show_loading(html);
    }

    var handler = function(response) {
                    this.show_dialog(response.getPayload().responseText);
                  }.bind(this);

    var error_handler = function(response) {
                          ErrorDialog.showAsyncError(response);
                          this.hide(false);
                        }.bind(this);

    var async = new AsyncRequest()
    .setOption('suppressEvaluation', true)
    .setURI(src)
    .setData(post_vars || {})
    .setHandler(handler)
    .setErrorHandler(error_handler)
    .setTransportErrorHandler(error_handler);


    if (!post_vars) {
      async.setMethod('GET').setReadOnly(true);
    }

    async.send();
    return this;
}

// shows a pop dialog with an ajax request and uses that innerHTML
// if post_vars is passed, then does a POST with those variables, otherwise just does a GET
generic_dialog.prototype.show_ajax_dialog=function(src, post_vars) {
  post_vars = post_vars || false;
  var load = tx('sh:loading');
  return this.show_ajax_dialog_custom_loader(load,src,post_vars);
}


// shows a dialog with the given title and body content
generic_dialog.prototype.show_prompt=function(title, content) {
  return this.show_dialog('<h2><span>' + title + '</span></h2><div class="dialog_content">' + content + '</div>');
}

// shows a message with a title, text, and button to continue
generic_dialog.prototype.show_message=function(title, content, button/* = 'Okay' */) {
  if (button == null) {
    button = tx('sh:ok-button');
  }
  return this.show_choice(title, content, button, function() {generic_dialog.get_dialog(this).fade_out(100)});
}

// shows a message with one or two buttons that do some javascript
generic_dialog.prototype.show_choice=function(title, content, button1, button1js, button2, button2js, buttons_msg, button3, button3js) {

  var buttons='<div class="dialog_buttons" id="dialog_buttons">';
  if (typeof(buttons_msg) != 'undefined') {
    buttons+='<div class="dialog_buttons_msg">';
    buttons+=buttons_msg;
    buttons+='</div>';
  }
  buttons+='<input class="inputsubmit" type="button" value="' + button1 + '" id="dialog_button1" />';
  if (button2) {
    var button2_class = 'inputsubmit';
    if (button2 == tx('sh:cancel-button')) {
      button2_class += ' inputaux';
    }
    buttons+='<input class="'+button2_class+'" type="button" value="' + button2 + '" id="dialog_button2" />';
  }
  if (button3) {
    var button3_class = 'inputsubmit';
    if (button3 == tx('sh:cancel-button')) {
      button3_class += ' inputaux';
    }
   buttons+='<input class="'+button3_class+'" type="button" value="' + button3 + '" id="dialog_button3" />';
  }
  this.show_prompt(title, this.content_to_markup(content) + buttons);

  // Register objects
  var inputs=this.obj.getElementsByTagName('input');
  if (button3) {
        button1obj=inputs[inputs.length-3];
        button2obj=inputs[inputs.length-2];
        button3obj=inputs[inputs.length-1];
  } else if (button2) {
    button1obj=inputs[inputs.length-2];
    button2obj=inputs[inputs.length-1];
  } else {
    button1obj=inputs[inputs.length-1];
  }

  // Assign JS to buttons if necessary
  if (button1js && button1) {
    if (typeof button1js == 'string') {
      eval('button1js = function() {' + button1js + '}');
    }
    button1obj.onclick=button1js;
  }
  if (button2js && button2) {
    if (typeof button2js == 'string') {
      eval('button2js = function() {' + button2js + '}');
    }
    button2obj.onclick=button2js;
  }
  if (button3js && button3) {
    if (typeof button3js == 'string') {
      eval('button3js = function() {' + button3js + '}');
    }
    button3obj.onclick=button3js;
  }

  if (!this.modal) {
    /**
     * Enter clicks the first button. Escape clicks the second one if it exists
     * (usually cancel), or else clicks the first button.
     */
    document.onkeyup = function(e) {
      var keycode = (e && e.which) ? e.which : event.keyCode;
      var btn2_exists = (typeof button2obj != 'undefined');
      var btn3_exists = (typeof button3obj != 'undefined');
      var is_webkit = ua.safari();

      if (is_webkit && keycode == 13) {
        // WebKit/Safari doesn't support enter-clicking on the focused item.
        button1obj.click();
      }

      // Escape clicks the first button if it's the only button.
      if (keycode == 27) {
        if (btn3_exists) {
          button3obj.click();
        } else if (btn2_exists) {
          button2obj.click();
        } else {
          button1obj.click();
        }
      }
      // Clear the onkeyup from these shackles.
      document.onkeyup = function() {}
    }
    // This should make enter work (except in Safari). If we always captured the
    // keycode too, it'd post twice in Firefox.
    this.button_to_focus = button1obj;
    button1obj.offsetWidth && button1obj.focus();
  }
  return this;
}


// shows a message with one or two buttons that do some javascript.
// content loaded from the server over AJAX.
generic_dialog.prototype.show_choice_ajax=function(title, content_src, button1, button1js, button2, button2js, buttons_msg, button3, button3js, readonly) {
  this.show_loading(tx('sh:loading'));

  var handler = function(response) {
    this.show_choice(title, response.getPayload(),
                     button1, button1js, button2, button2js, buttons_msg, button3, button3js);
  }.bind(this);

  var error_handler = function(response) {
    ErrorDialog.showAsyncError(response);
    this.hide(false);
  }.bind(this);


  var req = new AsyncRequest()
                 .setURI(content_src)
                 .setHandler(handler)
                 .setErrorHandler(error_handler)
                 .setTransportErrorHandler(error_handler);

  if (readonly == true) {
    req.setReadOnly(true);
  }
  req.send();
  return this;
}

/**
 * Loads the initial content of the dialog from src over AJAX.  (Payload should
 * NOT contain a <form> element.)  When the Okay button is clicked, the form is
 * submitted as a POST to the same src page.  If the POST succeeds, we display
 * the payload in the form, before fading away.  If it errors with a
 * kError_Global_ValidationError error, we just let them try the form again.
 * If any other kind of error comes back, we show the error with just an Okay
 * button to dismiss.
 */
generic_dialog.prototype.show_form_ajax = function(title, src, button, reload_page_on_success) {
  this.show_loading(tx('sh:loading'));

  var form_id = 'dialog_ajax_form__' + gen_unique();

  var preSubmitErrorHandler = function(dialog, response) {
    if (response.getError() != true) {
      dialog.hide();
      ErrorDialog.showAsyncError(response);
    } else {
      dialog.show_choice(title, response.getPayload(), 'Okay', function() { dialog.fade_out(200); });
    }
  }.bind(null, this);

  var preSubmitHandler = function(dialog, response) {
    var contents = '<form id="' + form_id + '" onsubmit="return false;">' + response.getPayload() + '</form>';
    dialog.show_choice(title, contents, button, submitHandler,
      tx('sh:cancel-button'), function() { dialog.fade_out(200); });
  }.bind(null, this);

  var submitHandler = function() {
    new AsyncRequest()
      .setURI(src)
      .setData(serialize_form(ge(form_id)))
      .setHandler(postSubmitHandler)
      .setErrorHandler(postSubmitErrorHandler)
      .send();
  };

  var postSubmitHandler = function(dialog, response) {
    dialog.show_choice(title, response.getPayload(), 'Okay', function() { dialog.fade_out(200); });
    if (reload_page_on_success) {
      window.location.reload();
    } else {
      setTimeout(function() { dialog.fade_out(500); }, 750);
    }
  }.bind(null, this);

  var postSubmitErrorHandler = function(dialog, response) {
    if (response.getError() == 1346001 /* kError_Global_ValidationError */) {
      preSubmitHandler(response);  // retry
    } else if (response.getError() != true) {
      ErrorDialog.showAsyncError(response);
    } else {
      preSubmitErrorHandler(response);  // abort
    }
  }.bind(null, this);

  new AsyncRequest()
    .setURI(src)
    .setReadOnly(true)
    .setHandler(preSubmitHandler)
    .setErrorHandler(preSubmitErrorHandler)
    .send();

  return this;
}


// shows a form that will cause a post
generic_dialog.prototype.show_form=function(title, content, button, target, submit_callback) {
  content='<form action="' + target + '" method="post">' + this.content_to_markup(content);
  var post_form_id=ge('post_form_id');
  if (post_form_id) {
    content+='<input type="hidden" name="post_form_id" value="' + post_form_id.value + '" />';
  }
  content+='<div class="dialog_buttons" id="dialog_buttons"><input class="inputsubmit" id="dialog_confirm" name="dialog_confirm" type="submit" value="' + button + '" />';
  content+='<input type="hidden" name="next" value="'+htmlspecialchars(document.location.href)+'"/>';
  content+='<input class="inputsubmit inputaux" type="button" value="'+tx('sh:cancel-button')+'" onclick="generic_dialog.get_dialog(this).fade_out(100)" /></form>';
  this.show_prompt(title, content);
  var submitButton = ge('dialog_confirm');
  submitButton.onclick = function(){window[submit_callback] && window[submit_callback]();}
  return this;
}

generic_dialog.prototype.content_to_markup=function(content) {
  return (typeof content == 'string') ?
         '<div class="dialog_body">' + content + '</div>' :
         '<div class="dialog_summary">'+ content.summary +'</div><div class="dialog_body">'+ content.body +'</div>';
}

// hides the dialog
generic_dialog.prototype.hide = function(temporary) {
  if (this.obj) {
    this.obj.style.display='none';
  }
  if (this.iframe) {
    this.iframe.style.display='none';
  }
  if (this.overlay) {
    this.overlay.style.display='none';
  }

  // clear any pending timeouts on the dialog
  if (this.timeout) {
    clearTimeout(this.timeout);
    this.timeout = null;
    return;
  }

  // unhide hidden objects
  if (this.hidden_objects.length) {
    for (var i = 0, il = this.hidden_objects.length; i < il; i++) {
      this.hidden_objects[i].style.visibility = '';
    }
    this.hidden_objects = [];
  }
  clearInterval(this.active_hiding);

  // if this is going away forever we want to remove it from the stack of dialogs
  if (!temporary) {
    if (generic_dialog.dialog_stack) {
      var stack = generic_dialog.dialog_stack;
      for (var i = stack.length - 1; i >= 0; i--) {
        if (stack[i] == this) {
          stack.splice(i, 1);
        }
      }
      if (stack.length) {
        stack[stack.length - 1].show();
      }
    }

    // destroy everything
    if (this.obj) {
      this.obj.parentNode.removeChild(this.obj);
      this.obj = null;
    }

    if (this.close_handler) {
      this.close_handler();
    }
  }

  return this;
}

// fades the dialog out over X seconds
generic_dialog.prototype.fade_out=function(interval, timeout, callback) {
  if (!this.popup) {
    // don't die if the popup isn't showing
    return this;
  }

  animation(this.obj).duration(timeout ? timeout : 0).checkpoint()
                     .to('opacity', 0).hide().duration(interval ? interval : 350)
                     .ondone(function() { callback && callback(); this.hide(); }.bind(this, {callback:callback})).go();
  return this;
}

// shows the dialog (if it's built already)
generic_dialog.prototype.show = function() {
  // show all of these elements for the dialog
  if (this.obj && this.obj.style.display) {
    this.obj.style.visibility='hidden';
    this.obj.style.display='';
    this.reset_dialog();
    this.obj.style.visibility='';
    this.obj.dialog=this; // for onclick events, etc
  } else {
    this.reset_dialog();
  }

  // hide objects that may clash with this (flash)
  this.hide_objects();
  clearInterval(this.active_hiding);
  this.active_hiding = setInterval(this.active_resize.bind(this), 500);

  // hide the current dialog if there is one (and it's not stackable)
  var stack = generic_dialog.dialog_stack ? generic_dialog.dialog_stack : generic_dialog.dialog_stack = [];
  if (stack.length) {
    var current_dialog = stack[stack.length - 1];
    if (current_dialog != this && !current_dialog.is_stackable) {
      current_dialog.hide();
    }
  }

  // put this at the top of the dialogpro stack
  for (var i = stack.length - 1; i >= 0; i--) {
    if (stack[i] == this) {
      stack.splice(i, 1);
    } else {
      stack[i].hide(true);
    }
  }
  stack.push(this);
  return this;
}

// enables \ disables all buttons in the dialog
generic_dialog.prototype.enable_buttons = function(enable) {
  var inputs = this.obj.getElementsByTagName('input');
  for (var i=0; i<inputs.length; i++) {
    if (inputs[i].type == 'button' || inputs[i].type == 'submit') {
      inputs[i].disabled = !enable;
    }
  }
}

generic_dialog.prototype.active_resize = function() {
  if (this.last_offset_height != this.content.offsetHeight) {
    this.hide_objects();
    this.last_offset_height = this.content.offsetHeight;
  }
}

// hides <embeds> under this object
generic_dialog.prototype.hide_objects = function() {
  var hide = [], objects = [];
  var ad_locs = ['', 0, 1, 2, 4, 5, 9, 3];

  // check for ad blocks to hide
  for (var i = 0; i < ad_locs.length; i++) {
    var ad_div = ge('ad_'+ad_locs[i]);
    if (ad_div != null) {
      hide.push(ad_div);
    }
  }

  // this is the bounding area of the dialog
  var rect = {x:elementX(this.content), y:elementY(this.content), w:this.content.offsetWidth, h:this.content.offsetHeight};

  // find all iframes that are "bad" on the page
  if (this.should_hide_objects) {
    var iframes = document.getElementsByTagName('iframe');
    for (var i = 0; i < iframes.length; i++) {
      if (iframes[i].className.indexOf('share_hide_on_dialog') != -1) {
        objects.push(iframes[i]);
      }
    }
  }

  // swfs (can by either <embed /> or <object />)
  var swfs = getElementsByTagNames('embed,object');
  for (var i = 0; i < swfs.length; i++) {
    if ((swfs[i].getAttribute('wmode') || '').toLowerCase() != 'transparent' || this.should_hide_objects) {
      objects.push(swfs[i]);
    }
  }

  // check if they intersect
  for (var i = 0; i < objects.length; i++) {
    var node = objects[i].offsetHeight ? objects[i] : objects[i].parentNode;
    swf_rect={x:elementX(node), y:elementY(node), w:node.offsetWidth, h:node.offsetHeight};
    if (!is_descendent(objects[i], this.content) &&
        rect.y + rect.h > swf_rect.y &&
        swf_rect.y + swf_rect.h > rect.y &&
        rect.x + rect.w > swf_rect.x &&
        swf_rect.x + swf_rect.w > rect.w &&
        this.hidden_objects.indexOf(node) == -1) {
          hide.push(node);
    }
  }

  // and hide
  for (var i = 0; i < hide.length; i++) {
    this.hidden_objects.push(hide[i]);
    hide[i].style.visibility = 'hidden';
  }
}

// builds a dialog base
generic_dialog.prototype.build_dialog=function() {
  // build a holder
  if (!this.obj) {
    this.obj = document.createElement('div');
  }
  this.obj.className = 'generic_dialog' + (this.className ? ' ' + this.className : '');
  this.obj.style.display = 'none';

  // Do this onload in case there's a dialog built inline (it will mess up in IE6\IE7)
  onloadRegister(function() {
    document.body.appendChild(this.obj);
  }.bind(this));

  // build an iframe to block out select boxes, or if the dialog is modal and user
  // are running IE7
  if (this.should_use_iframe || (this.modal && ua.ie() == 7)) {
    this.build_iframe();
  }

  // build a div to hold the content
  if (!this.popup) {
    this.popup=document.createElement('div');
    this.popup.className = 'generic_dialog_popup';
  }
  this.popup.style.left = this.popup.style.top = '';
  this.obj.appendChild(this.popup);

  // build a div to make modal overlay
  if (this.modal) {
    this.build_overlay();
  }
}

generic_dialog.prototype.build_iframe=function() {
  if (!this.iframe && !(this.iframe=ge('generic_dialog_iframe'))) {
    this.iframe = document.createElement('iframe');
    this.iframe.id = 'generic_dialog_iframe';
    this.iframe.src = "/common/blank.html";
  }
  this.iframe.frameBorder = '0';
  onloadRegister(function() {
    document.body.appendChild(this.iframe);
  }.bind(this));
}

generic_dialog.prototype.build_overlay=function() {
  this.overlay = document.createElement('div');
  this.overlay.id = 'generic_dialog_overlay';
  if (document.body.clientHeight > document.documentElement.clientHeight) {
    this.overlay.style.height = document.body.clientHeight+'px';
  } else {
    this.overlay.style.height = document.documentElement.clientHeight+'px';
  }
  onloadRegister(function() {
    document.body.appendChild(this.overlay);
  }.bind(this));
}

// repositions the elements to be where they should be
generic_dialog.prototype.reset_dialog = function() {
  if (!this.popup) {
    return;
  }
  onloadRegister(function() {
    this.reset_dialog_obj();
    this.reset_iframe();
  }.bind(this));
}

// sizes the iframe to go behind the dialog content,
// unless it is a modal dialog, which makes the iframe
// the whole page
generic_dialog.prototype.reset_iframe = function() {
  if (!this.should_use_iframe && !(this.modal && ua.ie() == 7)) {
    return;
  }
  if (this.modal) {
    this.iframe.style.left = '0px';
    this.iframe.style.top = '0px';
    this.iframe.style.width = '100%';
    if ((document.body.clientHeight > document.documentElement.clientHeight) &&
        (document.body.clientHeight < 10000)) {
      this.iframe.style.height = document.body.clientHeight+'px';
    } else if ((document.body.clientHeight < document.documentElement.clientHeight) &&
               (document.documentElement.clientHeight < 10000)) {
      this.iframe.style.height = document.documentElement.clientHeight+'px';
    } else {
      this.iframe.style.height = '10000px';
    }
  } else {
    this.iframe.style.left = elementX(this.frame)+'px';
    this.iframe.style.top = elementY(this.frame)+'px';
    this.iframe.style.width = this.frame.offsetWidth+'px';
    this.iframe.style.height = this.frame.offsetHeight+'px';
  }
  this.iframe.style.display = '';
}

// does nothing
generic_dialog.prototype.reset_dialog_obj=function() {}

// returns the dialog object in which obj is contained
/*static*/ generic_dialog.get_dialog=function(obj) {
  while (!obj.dialog && obj.parentNode) {
    obj=obj.parentNode;
  }
  return obj.dialog?obj.dialog:false;
}


// class for centered dialog with flat transparent borders
// (callback_function is a function that is executed after the dialog has fully loaded and rendered [it is mainly used
// to focus the textarea on the share popup])
function pop_dialog(className, callback_function, modal) {
  this.top = 125;
  this.parent.construct(this, className, modal);
  this.on_show_callback = callback_function;
}
pop_dialog.extend(generic_dialog);
pop_dialog.prototype.do_expand_animation = false;
pop_dialog.prototype.kill_expand_animation = true;

pop_dialog.prototype.show_ajax_dialog=function(src, post_vars, title) {
  post_vars = post_vars || false;
  if (this.do_expand_animation && !this.kill_expand_animation) {
      var load = null;
      this.show_loading_title(title);
    } else {
      var load = tx('sh:loading');
    }
  return this.show_ajax_dialog_custom_loader(load,src,post_vars);
}

pop_dialog.prototype.show_message=function(title, content, button/* = 'Okay' */) {
  if (this.do_expand_animation && !this.kill_expand_animation) {
    this.show_loading_title(title);
  } else {
    this.show_loading();
  }
  return this.parent.show_message(title, content, button);
}

pop_dialog.prototype.show_dialog=function(html, prevent_expand_animation) {

  var new_dialog = this.parent.show_dialog(html);

  if (this.do_expand_animation && !prevent_expand_animation && !this.kill_expand_animation) {

    function check_done_loading_title(callback, i) {
      var i = (i ? i : 0);
      if (this.done_loading_title != true && i < 10) {
        i++;
        setTimeout(check_done_loading_title.bind(this, callback, i), 50);
      } else {
        callback && callback();
      }
    }

    // Tries to ensure all the images have loaded
    function check_for_complete_images(content, callback, attempt) {
      var complete_images = 0;
      var images = content.getElementsByTagName('img');
      var safari2 = ua.safari() < 3;
      for(var i=0; i < images.length; i++) {
        var imageobj = images[i];
        if (image_has_loaded(imageobj)) {
          complete_images++;
        }
      }
      if (complete_images != images.length) {
        if (attempt < 20) {
          attempt++;
          setTimeout(function() { check_for_complete_images(content, callback, attempt); }, 100);
        } else {
          callback();
        }
      } else {
        callback();
      }
    }

    var divs = this.content.getElementsByTagName('div');
    for (var i=0; i < divs.length; i++) {
      if (divs[i].className == 'dialog_content') {
        expand_animation_div = divs[i];
        break;
      }
    }

    var container_div = document.createElement('div');
    container_div.style.padding = '0px';
    container_div.style.margin = '0px';
    container_div.style.overflow = 'visible';
    expand_animation_div.parentNode.insertBefore(container_div, expand_animation_div);
    container_div.appendChild(expand_animation_div);
    expand_animation_div.style.overflow = 'hidden';

    check_for_complete_images(expand_animation_div, function() {
      check_done_loading_title.bind(this, function() {
        this.content.getElementsByTagName('h2')[0].className = '';
        animation(expand_animation_div).to('height', 'auto').from(0).from('opacity', 0).to(1).ease(animation.ease.both).show().duration(200).ondone(
          function() {
            container_div.parentNode.insertBefore(expand_animation_div, container_div);
            container_div.parentNode.removeChild(container_div);
            if (!this.button_to_focus) {
              var inputs = this.obj.getElementsByTagName('input');
              for (var i= 0; i < inputs.length; i++) {
                if (inputs[i].type == 'button' && inputs[i].id == 'dialog_button1') {
                  // hack for animation.js -> container_div for blind() isn't removed until after the animation on_done callback... so wait a bit
                  this.button_to_focus = inputs[i];
                  break;
                }
              }
            }
            if (this.button_to_focus) {
              setTimeout(
                function() {
                  this.button_to_focus.focus();
                }.bind(this), 50);
            }
            expand_animation_div.style.overflow = 'visible'
            this.do_expand_animation = false;
            this.show();
          }.bind(this, {expand_animation_div:expand_animation_div, container_div: container_div})
        ).go();
      }.bind(this))();
    }.bind(this, {expand_animation_div: expand_animation_div}), 0);
  }

  return new_dialog;
}

// builds a pop dialog -- uses tables, but compatible in all browsers
pop_dialog.prototype.build_dialog=function() {
  this.parent.build_dialog();

  this.obj.className += ' pop_dialog';
  this.popup.innerHTML = '<table id="pop_dialog_table" class="pop_dialog_table">'+
                         '<tr><td class="pop_topleft"></td><td class="pop_border"></td><td class="pop_topright"></td></tr>'+
                         '<tr><td class="pop_border"></td><td class="pop_content" id="pop_content"></td><td class="pop_border"></td></tr>'+
                         '<tr><td class="pop_bottomleft"></td><td class="pop_border"></td><td class="pop_bottomright"></td></tr>'+
                         '</table>';
  this.frame = this.popup.getElementsByTagName('tbody')[0];
  this.content = this.popup.getElementsByTagName('td')[4];
}

// centers the dialog where it should be
pop_dialog.prototype.reset_dialog_obj=function() {
  this.popup.style.top=(document.documentElement.scrollTop?document.documentElement.scrollTop:document.body.scrollTop)+this.top+'px';
}

// sets the offset of the dialog from the top of the page
pop_dialog.prototype.set_top = function(top) {
  this.top = top;
}

// shows a dialog with the given title and body content
pop_dialog.prototype.show_prompt=function(title, content) {
  if (!this.do_expand_animation || this.kill_expand_animation) {
    return this.show_dialog('<h2><span>' + title + '</span></h2><div class="dialog_content">' + content + '</div>');
  }
  return this.show_dialog('<h2 class="dialog_loading"><span>' + title + '</span></h2><div class="dialog_content" style="display:none;">' + content + '</div>');
}

pop_dialog.prototype.show_loading_title = function(title) {
  if (!this.kill_expand_animation) {
    this.do_expand_animation = true;
    this.show_dialog('<h2 class="dialog_loading"><span>' + title + '</span></h2>', true);
    // we want to hold this state for a bit before we show the dialog to preserve the effect of the animation
    setTimeout(function() { this.done_loading_title = true; }.bind(this), 200);
  } else {
   this.show_loading();
  }
}

//
// class for contextual dialogs pointing to what they reference. think: mini-feed
function contextual_dialog(className) {
  this.parent.construct(this, className);
}
contextual_dialog.extend(generic_dialog);

// sets the context for which this element will be used... i.e. what it's going to point to
contextual_dialog.prototype.set_context=function(obj) {
  this.context=obj;
  return this;
}

// builds a contextual dialog
contextual_dialog.prototype.build_dialog=function() {
  this.parent.build_dialog();

  this.obj.className += ' contextual_dialog';
  this.popup.innerHTML = '<div class="contextual_arrow"><span>^_^keke1</span></div><div class="contextual_dialog_content"></div>';
  this.arrow = this.popup.getElementsByTagName('div')[0];
  this.content = this.frame = this.popup.getElementsByTagName('div')[1];
}

// sets this dialog near its context.
contextual_dialog.prototype.reset_dialog_obj = function() {
  var x = elementX(this.context);
  var center = (document.body.offsetWidth - this.popup.offsetWidth) / 2;
  if (x < document.body.offsetWidth / 2) {
    this.arrow.className = 'contextual_arrow_rev';
    var left = Math.min(center, x + this.context.offsetWidth - this.arrow_padding_x);
    var arrow = x - left + this.context.offsetWidth + this.arrow_padding_x;
  } else {
    this.arrow.className = 'contextual_arrow';
    var left = Math.max(center, x - this.popup.offsetWidth + this.arrow_padding_x);
    var arrow = x - left - this.arrow_padding_x - this.arrow_width;
  }

  if (isNaN(left)) {
    left = 0;
  }

  if (isNaN(arrow)) {
    arrow = 0;
  }

  this.popup.style.top = (elementY(this.context) + this.context.offsetHeight - this.arrow.offsetHeight + this.arrow_padding_y)+'px';
  this.popup.style.left = left + 'px';
  this.arrow.style.backgroundPosition = arrow + 'px';
}

// kill all scroll events on this dialog
contextual_dialog.prototype._remove_resize_events = function() {
  if (this._scroll_events) {
    for (var i = 0; i < this._scroll_events.length; i++) {
      removeEventBase(this._scroll_events[i].obj, this._scroll_events[i].event, this._scroll_events[i].func);
    }
  }
  this._scroll_events = [];
}

// setup hooks to reposition on resize
contextual_dialog.prototype.show = function() {
  this._remove_resize_events();
  var obj = this.context;
  while (obj) {
    if (obj.id != 'content' &&
        (obj.scrollHeight && obj.offsetHeight && obj.scrollHeight != obj.offsetHeight) ||
        (obj.scrollWidth && obj.offsetWidth && obj.scrollWidth != obj.offsetWidth)) {
      var evt = {obj: obj, event: 'scroll', func: this.reset_dialog_obj.bind(this)};
      addEventBase(evt.obj, evt.event, evt.func);
    }
    obj = obj.parentNode;
  }
  var evt = {obj: window, event: 'resize', func: this.reset_dialog_obj.bind(this)};
  addEventBase(evt.obj, evt.event, evt.func);
  this.parent.show();
}
contextual_dialog.prototype.hide = function(temp) {
  this._remove_resize_events();
  this.parent.hide(temp);
}

contextual_dialog.prototype.arrow_padding_x = 5;
contextual_dialog.prototype.arrow_padding_y = 10;
contextual_dialog.prototype.arrow_width = 13;

contextual_dialog.hide_all = function(callback) {
  if (generic_dialog.dialog_stack) {
    for(var i=0; i < generic_dialog.dialog_stack.length; i++) {
      if (generic_dialog.dialog_stack[i].context && generic_dialog.dialog_stack[i].arrow) {
        generic_dialog.dialog_stack[i].hide();
      }
    }
  }
  callback && callback();
}



/**
 *  An error dialog for showing errors to the end user.
 *
 *    new ErrorDialog( )
 *      .showError(
 *        'Something Bad Happened',
 *        'Something bad happened, sorry.');
 *
 *  You can use the static method showAsyncError() as a callback handler for
 *  AsyncRequests:
 *
 *    new AsyncRequest( )
 *      .setErrorHandler(ErrorDialog.showAsyncError);
 *
 *  In fact, it's the default handler.
 *
 *  @author epriestley
 */
function /* class */ ErrorDialog( ) /* extends pop_dialog */ {

  this.parent.construct(
    this,
    'errorDialog',
    null,
    true);

  return this;
};

ErrorDialog.extend(pop_dialog);

copy_properties(ErrorDialog.prototype, {

  /**
   *  Show an error dialog.
   *
   *  @access public
   *  @author epriestley
   */
  showError : function(title, message) {
    return this.show_message(title, message);
  }

});

copy_properties(ErrorDialog, {

  /**
   *  Show an asynchronous error dialog.
   *
   *  @access public
   *  @author epriestley
   */
  showAsyncError : function(response) {
    try {
      return (new ErrorDialog( ))
        .showError(
          response.getErrorSummary( ),
          response.getErrorDescription( ));
    } catch (ex) {
      alert(response);
    }
  }

});



  /**************  typeahead_ns.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

var Registry = [];
var _registryIndex = 0;
var _lastKeyCode = -1;
var _names;
var _ids;
var _images;
var _networks;

var TypeAhead = function(rootEl, formEl, textBoxEl, idEl, defaultOptions, instructions, useFilter, onSuccessHandler, onInputChangeHandler, onUpHandler, onDownHandler, onListElMouseDownHandler, placeholderText, showNoMatches, override_resize)
{
  this.resize=!override_resize;

  this.getMatchSingleTerm = function(term, document)
  {
    var str = "";
    var len = term.length;
    if (!document) return '';
    var curDocument = document;

    // first check at beginning of string.
    var index = 0;
    index = curDocument.toUpperCase().indexOf(term.toUpperCase());
    if (index == -1)
    {
      return str;
    }

    var match = curDocument.substring(0, len);
    str += '<span class="suggest">' + match + '</span>';

    var moreMatches = 0;
    curDocument = curDocument.substring(index+len);
    while((index = curDocument.toUpperCase().indexOf(term.toUpperCase())) != -1)
    {
      var pre = curDocument.substring(0, index);
      if (pre)
      {
        str += pre;
      }
      var match = curDocument.substring(index, index+len);
      if (match)
      {
        str += '<span class="suggest">' + match + '</span>';
      }
      curDocument = curDocument.substring(index+len);
      moreMatches = 1;
    }
    if (moreMatches)
    {
      str += curDocument;
    }
  }

  this.getMatchMultipleTerms = function(terms, document)
  {
    if (!document) return '';
    var termsArr = terms.split(/\s+/);
    var docArr = document.split(/\s+/);

    var str = "";
    for (var docIdx = 0; docIdx < docArr.length; docIdx++)
    {
      var matchFound = 0;
      var doc = docArr[docIdx];
      for (var termIdx = 0; termIdx < termsArr.length; termIdx++)
      {
        var term = termsArr[termIdx];

        // if we found a match
        if (doc.toUpperCase().indexOf(term.toUpperCase()) == 0)
        {
          matchFound = 1;
          break;
        }
      }

      if (docIdx > 0)
      {
        str += ' ';
      }

      if (matchFound)
      {
        var len = term.length;
        var pre = doc.substring(0, len);
        var suf = doc.substring(len);
        str += '<span class="suggest">' + pre + '</span>' + suf;
      }
      else
      {
        str += doc;
      }
    }

    return str;
  }

  this.onListChange = function()
  {
    this.selectedIndex = -1;
    if (!this.pEvent)
    {
      this.idEl.value = 0;
    }
    var dropDownEl = this.dropDownEl;
    if (dropDownEl && dropDownEl.childNodes)
    {
      this.dropDownCount = dropDownEl.childNodes.length;
    }

    this.lastTypedValue = this.currentInputValue;
    if (this.currentInputValue == "" || this.dropDownCount == 0 || this.pEvent)
    {
      this.dropDownEl.hide();
//    this.defaultDropDownEl.hide();
    }
    else
    {
      this.dropDownEl.show();
      this.defaultDropDownEl.show();
    }

    var matchFound = false;
    if (this.currentInputValue.length > 0)
    {
      for (var i = 0; i < this.dropDownCount; i++)
      {
          if (!matchFound)
          {
            matchFound = true;
            this.selectedIndex = i;
            this.selectedEl = this.dropDownEl.childNodes[i];
          }

          // try matching the name
          var str = this.getMatchSingleTerm(this.currentInputValue, this.dropDownEl.childNodes[i]._value);
          if (!str)
          {
            str = this.getMatchMultipleTerms(this.currentInputValue, this.dropDownEl.childNodes[i]._value);
          }
          this.dropDownEl.childNodes[i].setName(str);

          // try matching the location
          str = this.getMatchSingleTerm(this.currentInputValue, this.dropDownEl.childNodes[i]._loc);
          if (!str)
          {
            str = this.getMatchMultipleTerms(this.currentInputValue, this.dropDownEl.childNodes[i]._loc);
          }
          this.dropDownEl.childNodes[i].setLoc(str);
      }

      if (!matchFound)
      {
        for (var i = 0; i < this.defaultDropDownCount; i++)
        {
          if (this.defaultDropDownEl.childNodes[i]._value.toUpperCase().indexOf(this.currentInputValue.toUpperCase()) == 0)
          {
            matchFound = true;
            this.selectedIndex = i;
            this.selectedEl = this.defaultDropDownEl.childNodes[i];
            break;
          }
        }
      }
    }

    var value = this.currentInputValue;

    var keyIgnore = false;
    switch (this.lastKeyCode)
    {
      case 8:
      case 33:
      case 34:
      case 35:
      case 35:
      case 36:
      case 37:
      case 39:
      case 45:
      case 46:
        keyIgnore = true;
        break;
      case 27:
        keyIgnore = true;
        break;
      default:
        break;
    }

    if (!keyIgnore && matchFound && !this.pEvent /* IE focus bug */)
    {
      this.selectedEl.select();
    }
    else
    {
    }

    this._noMatches = false;
    if (this.dropDownCount == 0)
    {
      if (this.textBoxEl.value != "" && this.textBoxEl.value != this.textBoxEl.ph)
      {
        this._noMatches = true;
        if (this.showNoMatches)
        {
          this.defaultTextEl.setText(tx('typeahead_ns:no-matches'));
        }
      }
      else
      {
        this.defaultTextEl.setDefault();
      }

      this.defaultDropDownEl.show();

      if (this.showNoMatches)
      {
        this.defaultTextEl.show();
      }
    }
    else
    {
      this.defaultTextEl.hide();
    }

    if (this.dropDownCount >= 1 && this.selectedEl && this.getUnselectedLength() == this.selectedEl._value.length)
    {
      this.idEl.value = this.selectedEl._id;
      if (this.dropDownCount == 1) {
        this.onTypeAheadSuccess();
      } else {
        this.textBoxEl.style.background = "#e1e9f6";
      }
    }
    else
    {
      this.onTypeAheadFailure();
    }
    if (this.lastKeyCode == 27)
    {
      this.textBoxEl.blur();
    }

    this.setFrame();
    this.pEvent = 0;
  }

  this.setFrame = function()
  {
    if (this.goodFrame)
    {
      this.goodFrame.style.height = (this.containerEl.offsetHeight) + "px";
      this.goodFrame.style.width = (this.textBoxEl.offsetWidth) + "px";
    }
  }

  this.onTypeAheadSuccess = function()
  {
    this.dropDownEl.hide();
    this.textBoxEl.style.background = "#e1e9f6";
    if (this.onSuccess && !this.pEvent)
    {
      this.onSuccess(this);
    }
  }

   this.onTypeAheadFailure = function()
  {
    this.textBoxEl.style.background = "#FFFFFF";
  }

  this.refocus = function()
  {
    this.reFocused = true;
    this.textBoxEl.blur();
    setTimeout("Registry[" + this.registryIndex + "].focus();", 10);
  }

  this.focus = function()
  {
    this.textBoxEl.focus();
  }

  this.handleKeyUp = function(event)
  {
    if (!event && window.event)
    {
      event = window.event;
    }


    // avoids double-firing of events in safari
    if (event.keyCode == 40 || event.keyCode == 38)
    {
        if (this.isSafari && (this.fireCount++ % 2 == 1))
        {
      //    return;
        }

  //    this.refocus();
    }

    // fast typing check
    var value = this.textBoxEl.value;
    var sLen = this.getSelectedLength();
    var uLen = this.getUnselectedLength();
    if (sLen > 0 && uLen != -1)
    {
      value = value.substring(0, uLen);
    }
    this.currentInputValue = value;

    var keyIgnore = false;
    switch (this.lastKeyCode)
    {
      case 13:
      case 9:
        keyIgnore = true;
        break;
      case 38:
        keyIgnore = true;
//        this.selectPrevDropDown();
        if (this.onUp)
        {
          this.onUp(this);
        }
        break;
      case 40:
        keyIgnore = true;
//        this.selectNextDropDown();
        if (this.onDown)
        {
          this.onDown(this);
        }
        break;
    }

    this.pEvent = 0;
    if (event.pEvent)
    {
      this.pEvent = event.pEvent;
    }

    if (!keyIgnore && /*this.currentInputValue != this.lastInputValue &&*/ this.onInputChange)
    {
      this.onInputChange(this);
    }
    if (this.lastKeyCode == 13)
    {
      this.lastKeyCode = -1;
      _lastKeyCode = -1;
    }

    this.lastInputValue = this.currentInputValue;
  }

   this.getSelectedLength = function()
  {
    var el = this.textBoxEl;
    var len = -1;
    if (el.createTextRange)
    {
      var selection = document.selection.createRange().duplicate();
      len = selection.text.length;
    }
    else if (el.setSelectionRange)
    {
      len = el.selectionEnd - el.selectionStart;
    }
    return len;
  }


  this.getUnselectedLength = function()
  {
    var el = this.textBoxEl;
    var len = 0;
    if (el.createTextRange)
    {
      var selection = document.selection.createRange().duplicate();
      selection.moveEnd("textedit", 1);
      len = el.value.length - selection.text.length;
    }
    else if (el.setSelectionRange)
    {
      len = el.selectionStart;
    }
    else
    {
      len = -1;
    }
    return len;
  }

  this.handleKeyDown = function(event)
  {
    if (!event && window.event)
    {
      event = window.event;
    }
    if (event)
    {
      this.lastKeyCode = event.keyCode;
      _lastKeyCode = event.keyCode;
    }

    switch (this.lastKeyCode)
    {
      case 38:
        break;
      case 40:
        break;
      case 27:
        this.textBoxEl.value = "";
        break;
      case 13:
      case 9:
        //formEl.onsubmit();
        if (this.selectedIndex != -1)
        {
          this.textBoxEl.value = this.selectedEl._value;
          this.defaultTextEl.hide();
          this.onTypeAheadSuccess();
        }
        this.dropDownEl.hide();
        this.defaultDropDownEl.hide();
        this.setFrame();
        break;
      case 3:
        this.dropDownEl.hide();
        this.defaultDropDownEl.hide();
        this.setFrame();
        break;
    }

    switch (this.lastKeyCode)
    {
      case 38:
        this.selectPrevDropDown();
        if (this.onUp)
        {
          this.onUp(this);
        }
        break;
      case 40:
        this.selectNextDropDown();
        if (this.onDown)
        {
          this.onDown(this);
        }
        break;
    }

    if (event && (event.keyCode == 13 || event.keyCode == 38 || event.keyCode == 40))
    {
      event.cancelBubble = true;
       event.returnValue = false;
    }
  }

  this.selectPrevDropDown = function()
  {
    this.selectDropDown(this.selectedIndex-1);
  }
  this.selectNextDropDown = function()
  {
    this.selectDropDown(this.selectedIndex+1);
  }

  this.selectDropDown = function(index)
  {
    this.textBoxEl.value = this.lastTypedValue;
    if ((this.dropDownCount + this.defaultDropDownCount) <= 0)
    {
      return;
    }

    if (this.dropDownCount > 0)
    {
      this.dropDownEl.show();
      this.defaultDropDownEl.show();
    }
    else
    {
      this.dropDownEl.hide();
      //this.defaultDropDownEl.hide();
    }
    this.setFrame();

    var usingDefaultDropDown = false;
    if (index >= this.dropDownCount && this.defaultDropDownCount > 0)
    {
      usingDefaultDropDown = true;
    }

    if (index >= this.dropDownCount + this.defaultDropDownCount)
    {
      index = this.dropDownCount + this.defaultDropDownCount - 1;
    }

    if (this.selectedIndex != -1 && index != this.selectedIndex)
    {
      this.selectedIndex = -1;
      this.selectedEl.unselect();
    }

    if (index < 0)
    {
      this.selectedIndex = -1;

      // commented out. safari issue erasing the text box
//    this.textBoxEl.focus();
      return;
    }

    this.selectedIndex = index;
    if (usingDefaultDropDown)
    {
      this.selectedEl = this.defaultDropDownEl.childNodes[index-this.dropDownCount];
    }
    else
    {
      this.selectedEl = this.dropDownEl.childNodes[index];
    }
    this.selectedEl.select();

    this.textBoxEl.value = this.selectedEl._value;
  }

  this.displaySuggestList = function(names, ids, locs)
  {
    if (names.length != ids.length)
    {
      return false;
    }

    var dropDownEl = this.dropDownEl;
    while(dropDownEl.childNodes.length > 0)
    {
      dropDownEl.removeChild(dropDownEl.childNodes[0]);
    }

    if (this.selectedEl)
    {
      this.selectedEl.unselect();
    }

    //match_i used to cap items shown in non-ajax version
    var match_i = 0;
    var termsArr;
    var term;
    var matchFound;
    var name;
    var match_id;
    var filter = this.currentInputValue.toUpperCase();
    filter = filter.replace(/^\s+|\s+$/,'');
    for (var i = 0; i < names.length && match_i < 10; i++)
    {
      name = names[i];
      if (this.useFilter)
      {
        if (!filter)
        {
           continue;
        }

        match_id = ids[i];
        if (window._ignoreList && _ignoreList[match_id] && _ignoreList[match_id] == 1)
        {
          continue;
        }

        matchFound = 0;
        if (name.toUpperCase().indexOf(filter) == 0)
        {
          matchFound = 1;
        }

        if (!matchFound)
        {
          termsArr = name.split(/\s+/);
          for (var termIdx = 0; termIdx < termsArr.length; termIdx++)
          {
            term = termsArr[termIdx];
            if (term.toUpperCase().indexOf(filter) == 0)
            {
              matchFound = 1;
              break;
            }
          }
        }

        if (!matchFound)
        {
          continue;
        }

        match_i++;
      }

      var listEl = this.createListElement(name, ids[i], locs[i], i);
      dropDownEl.appendChild(listEl);
    }

    // now reset the indexes for the default drop down
    for (var i = 0; i < this.defaultDropDownEl.childNodes.length; i++)
    {
      var listEl = this.defaultDropDownEl.childNodes[i];
      listEl._index = i + this.dropDownEl.childNodes.length;
    }

    return true;
  }

  this.createListElement = function(name, id, loc, index)
  {
    var listEl = document.createElement("div");
    listEl._value = name;
    listEl._loc = loc;
    listEl._id = id;
    listEl._index = index;

    listEl.setName = function(name)
    {
      this.nameEl.innerHTML = name;
    }

    listEl.setLoc = function(loc)
    {
      if (this.locEl)
        this.locEl.innerHTML = loc;
    }

    listEl.select = function()
    {
      this.className = "list_element_container_selected";
      this.nameEl.className = "list_element_name_selected";
      if (this.locEl)
      {
        this.locEl.className = "list_element_loc_selected";
      }
      if (oThis.idEl)
      {
        oThis.idEl.value = this._id;
      }
    }

    listEl.unselect = function()
    {
      this.className = "list_element_container";
      this.nameEl.className = "list_element_name";
      if (this.locEl)
      {
        this.locEl.className = "list_element_loc";
      }
      if (oThis.idEl)
      {
    //    oThis.idEl.value = -1;
      }
      oThis.selectedIndex = -1;
    }

    listEl.onmousedown = function()
    {
      oThis.textBoxEl.value = this._value;
      if (oThis.idEl)
      {
        oThis.idEl.value = this._id;
      }
      oThis.onTypeAheadSuccess();

      if (oThis.formEl)
      {
     //   oThis.formEl.submit();
      }

      if (oThis.onListElMouseDown)
      {
        oThis.onListElMouseDown(oThis, this);
      }
      oThis.setFrame();
    }

    listEl.onmouseover = function()
    {
      if (oThis.selectedEl)
      {
        oThis.selectedEl.unselect();
      }
      oThis.selectedEl = this;
       oThis.selectedIndex = this._index;
      this.select();
    }

    listEl.onmouseout = function()
    {
      this.unselect();
    }
    listEl.style.zIndex = "101";

    var dividerEl;
    if (index == -1)
    {
      dividerEl = this.createDivider();
      listEl.appendChild(dividerEl);
    }

    var nameEl = document.createElement("div");
    nameEl.className = "list_element_name";
    nameEl.innerHTML = name;
    listEl.appendChild(nameEl);
    listEl.nameEl = nameEl;
    listEl.locEl = locEl;

    if (loc)
    {
      var locEl = document.createElement("div");
      locEl.className = "list_element_loc";
      locEl.innerHTML = loc;
      listEl.appendChild(locEl);
      listEl.locEl = locEl;
    }

    dividerEl = this.createDivider();
    listEl.appendChild(dividerEl);

    listEl.unselect();

    return listEl;
  }

  this.createDivider = function()
  {
    var dividerEl = document.createElement("div");
    dividerEl.className = "list_element_divider";
    return dividerEl;
  }

  this.createDropDownContainer = function()
  {
    var containerEl = document.createElement("div");
    containerEl.className = "dropdown-container";
    this.containerEl = containerEl;
    this.positionDropDown();
  }

  this.createDropDown = function()
  {
/*
    var dropDownHeaderEl = document.createElement("div");
    dropDownHeaderEl.className = "header";
    dropDownHeaderEl.style.display = "none";
    dropDownHeaderEl.innerHTML = "Did you mean...";

    this.containerEl.appendChild(dropDownHeaderEl);
    this.dropDownHeaderEl = dropDownHeaderEl;
*/

    var dropDownEl = document.createElement("div");
    dropDownEl.className = "dropdown";
    dropDownEl.style.display = "none";
    dropDownEl.style.zIndex = "101";

    dropDownEl.hide = function()
    {
      this.style.display = "none";
//      oThis.dropDownHeaderEl.style.display = "none";
    }
    dropDownEl.show = function()
    {
      this.style.display = "";
//      oThis.dropDownHeaderEl.style.display = "";

      // safari doesn't always position this correctly on initialization, so explicitly call it here.
      oThis.positionDropDown();
    }

    this.containerEl.appendChild(dropDownEl);
    this.dropDownEl = dropDownEl;
  }

  this.createDefaultDropDown = function()
  {
    var defaultDropDownHeaderEl = document.createElement("div");
    defaultDropDownHeaderEl.className = "typeahead_header";
    defaultDropDownHeaderEl.style.display = "none";
    defaultDropDownHeaderEl.innerHTML = tx('typeahead_ns:search-elsewhere');

    this.containerEl.appendChild(defaultDropDownHeaderEl);
    this.defaultDropDownHeaderEl = defaultDropDownHeaderEl;

    var defaultDropDownEl = document.createElement("div");
    defaultDropDownEl.style.display  = "none";

    defaultDropDownEl.show = function()
    {
      if (oThis.defaultDropDownCount > 0)
      {
        this.style.display = "";
        oThis.defaultDropDownHeaderEl.style.display = "";
      }
      else
      {
        oThis.dropDownEl.style.borderBottom = "1px solid #777";
      }
    }

    defaultDropDownEl.hide = function()
    {
      this.style.display = "none";
      oThis.defaultDropDownHeaderEl.style.display = "none";
    }

    var index = 0;
    for (var option in this.defaultOptions)
    {
      var listEl = this.createListElement(option, this.defaultOptions[option], "", index);
      index++;
      defaultDropDownEl.appendChild(listEl);
    }

    defaultDropDownEl.className = "default-dropdown";
    defaultDropDownEl.hide();
    this.containerEl.appendChild(defaultDropDownEl);
    this.defaultDropDownEl = defaultDropDownEl;
    this.defaultDropDownCount = defaultDropDownEl.childNodes.length;
  }

  this.createDefaultText = function()
  {
    var defaultTextEl = document.createElement("div");
    defaultTextEl.className = "default-text";
    defaultTextEl.style.display = "none";

    defaultTextEl.hide = function()
    {
      this.style.display = "none";
    }

    defaultTextEl.show = function()
    {
      this.style.display = "";
      if (oThis.defaultDropDownCount == 0)
      {
        this.style.borderBottom = "1px solid #777";
      }
    }

    defaultTextEl.setDefault = function()
    {
      this.innerHTML = instructions;
    }

    defaultTextEl.setText = function(text)
    {
      this.innerHTML = text;
    }

    defaultTextEl.setDefault();

    if (!this.defaultOptions)
    {
      defaultTextEl.style.borderBottom = "0px solid";
    }

    this.containerEl.appendChild(defaultTextEl);
    this.defaultTextEl = defaultTextEl;
  }

  this.positionDropDown = function()
  {
    var containerEl = this.containerEl;
    if (containerEl)
    {
      if (this.customOffsetElement) {
        containerEl.style.left = elementX(this.textBoxEl) - elementX(this.customOffsetElement) + "px";
        containerEl.style.top = elementY(this.textBoxEl) - elementY(this.customOffsetElement) + this.textBoxEl.offsetHeight + "px";
      }
      else if (this.resize) {
        containerEl.style.left = elementX(this.textBoxEl)  + "px";
        containerEl.style.top = elementY(this.textBoxEl) + this.textBoxEl.offsetHeight + "px";
      }
      if (!this.isIE)
      {
        containerEl.style.width = this.textBoxEl.offsetWidth + "px";
      }
      else
      {
        containerEl.style.width = this.textBoxEl.offsetWidth + "px";
      }
    }
  }

  this.getText = function()
  {
    return this.textBoxEl.value;
  }

  this.getSelectedText = function()
  {
    return this.selectedEl ? this.selectedEl._value : '';
  }

  this.noMatches = function()
  {
      return this._noMatches;
  }

  this.getID = function()
  {
    return this.selectedEl ? this.selectedEl._id : 0;
  }

  this.setText = function(q, reset)
  {
    this.textBoxEl.setText(q, reset);
  }

  this.init = function()
  {
        this._noMatches = false;
    this.registryIndex = _registryIndex;
    Registry[_registryIndex++] = this;

    this.lastKeyCode = -1;

    this.currentInputValue = textBoxEl.value;
    this.lastTypedValue = "";
    this.lastInputValue = "";

    this.dropDownCount = 0;
    this.defaultDropDownCount = 0;

    this.customOffsetElement = null;

    this.selectedIndex = -1;
    this.selectedEl = null;

    this.reFocused = false;

    textBoxEl.setAttribute("placeholder", placeholderText);
    textBoxEl.style.color = '#777';
    textBoxEl.ph = placeholderText;

    textBoxEl.oThis = this;

    textBoxEl.onblur = function()
    {
      if (!oThis.reFocused)
      {
        oThis.dropDownEl.hide();
        oThis.defaultTextEl.hide();
        oThis.defaultDropDownEl.hide();
      }
      if (oThis.selectedIndex == -1)
      {
        //this.value = "";
        oThis.idEl.value = 0;
      }
      oThis.reFocused = false;

      var ph = this.getAttribute("placeholder");
      if (this.isFocused && ph && (this.value == "" || this.value == ph))
      {
        this.isFocused = 0;
        this.value = ph;
        this.style.color = '#777';
      }
      oThis.setFrame();
    }

    textBoxEl.onfocus = function()
    {
            // need this because this is called from a setTimeout
            var oThis = this.oThis;
      if (!this.isFocused)
      {
        this.isFocused = 1;
        if (oThis.selectedIndex == -1 && this.value == this.getAttribute("placeholder"))
        {
          this.value = '';
        }

      }
            if (oThis.dropDownCount > 0 || oThis.defaultTextEl.innerHTML != '')
            {
                if (oThis.dropDownCount == 0) {
                    oThis.defaultTextEl.show();
                }

                if (this.createTextRange)
                {
                    var t = this.createTextRange();
                    t.moveStart("character", 0);
                    t.select();
                }
                else if (this.setSelectionRange)
                {
                    this.setSelectionRange(0, this.value.length);
                }

                oThis.dropDownEl.show();
                oThis.defaultDropDownEl.show();
                oThis.positionDropDown();
                oThis.setFrame();
            }
            this.style.color = '#000';
    }

    textBoxEl.onkeyup = function(event)
    {
      oThis.handleKeyUp(event);
    }

    textBoxEl.setText = function(q, reset)
    {
      var ph = this.getAttribute("placeholder");
      this.isFocused = 0;
      if (q)
      {
        this.style.color = '#000';
        this.value = q;
        var ev = new Object();
        ev.keyCode = 0;
        ev.pEvent = 1;
        oThis.handleKeyUp(ev);
      }

      else if (ph && ph != "")
      {
        if (reset)
        {
          this.value = "";
          this.style.color = '#000';
        }
        else
        {
          this.value = ph;
          this.style.color = '#777';
        }
        this.isFocused = 0;
        oThis.textBoxEl.style.background = "#FFFFFF";
      }
      else
      {
        this.value = "";
        oThis.textBoxEl.style.background = "#FFFFFF";
      }
    }

    if (!formEl) {
      formEl = textBoxEl.form;
    }
    if (formEl)
    {
      formEl.onsubmit = function()
      {
        oThis.setFrame();
        if (_lastKeyCode == 13)
        {
          _lastKeyCode = -1;
          return false;
        }
        if (oThis.selectedIndex != -1 && oThis.selectedEl)
        {
          oThis.idEl.value = oThis.selectedEl._id;
        }
        //this.submit();
        return true;
      }
    }
    this.formEl = formEl;

    this.textBoxEl = textBoxEl;

    this.idEl = idEl;
    this.onInputChange = onInputChangeHandler;
    this.onSuccess = onSuccessHandler;
    this.defaultOptions = defaultOptions;
    this.useFilter = useFilter;
    this.onUp = onUpHandler;
    this.onDown = onDownHandler;
    this.onListElMouseDown = onListElMouseDownHandler;
    this.showNoMatches = showNoMatches;

    this.fireCount = 0;
    this.isIE = 0;
    this.isSafari = 0;
    if (navigator)
    {
      this.browser = navigator.userAgent.toLowerCase();
      if (this.browser.indexOf("safari") != -1)
      {
        this.isSafari = 1;
      }
      if (this.browser.indexOf("msie") != -1)
      {
        this.isIE = 1;
      }
    }

    //var blank_spot = ge('blank_spot');
    var blank_spot = rootEl;
    this.createDropDownContainer();
    this.createDropDown();
    this.createDefaultText();
    this.createDefaultDropDown();
    this.positionDropDown();
    var savior = document.createElement("div");
    savior.id = "savior";
    this.containerEl.id = "dropdown";
    this.goodFrame = null;
    if (rootEl)
    {
      if (blank_spot && this.isIE)
      {
        rootEl.appendChild(savior);
      }
      rootEl.appendChild(this.containerEl);
    }

    if (blank_spot == rootEl && this.isIE)
    {
      var goodFrame = document.createElement('iframe');
      goodFrame.id = "goodFrame";
      goodFrame.src = "/common/blank.html";
      goodFrame.style.width = "0px";
      goodFrame.style.height = "0px";
      goodFrame.style.zIndex = "98";
      blank_spot.insertBefore(goodFrame, blank_spot.firstChild);
      blank_spot.style.zIndex="99";
      this.goodFrame = goodFrame;
    }
  }

  this.setCustomOffsetElement = function(el) {
    this.customOffsetElement = el;
  }

  var oThis = this;
  this.init();

  if (!window.onresize)
  {
    window.onresize = function(event)
    {
      for (var idx = 0; idx < Registry.length; idx++)
      {
        Registry[idx].positionDropDown();
      }
    }
  }

  textBoxEl.onkeydown = function(event)
  {
    oThis.handleKeyDown(event);
  }
}

function debug(str)
{
  document.getElementById("debug").innerHTML += str + "<BR>";
}

function city_selector_onfound(input, obj) {
  input.value = obj ? obj.i : -1;
}

function city_selector_onselect(success) {
  if (window[success]) {
    window[success]();
  }
}



  /**************  suggest.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

var Suggest = function(rootEl, q, formEl, textBoxEl, idEl, uri, param, successHandler, instructions, networkType, placeholderText, defaultOptions, showNoMatches, override_resize) {
  this.onInputChange = function() {
    var currentInputValue = oThis.typeAheadObj.currentInputValue;
    var cache = oThis.getCache(currentInputValue);
    if (cache) {
      oThis.onSuggestRequestDone(currentInputValue, cache[0], cache[1], cache[2]);
    } else {
      var typeStr = "";

      var data = {};
      data[oThis.suggestParam] = currentInputValue;
      if (oThis.networkType) {
        data['t'] = oThis.networkType;
      }

      var asyncRequestGet = new AsyncRequest()
        .setURI(oThis.suggestURI)
        .setData(data)
        .setHandler(function(response) {
          var payload = response.payload;
          oThis.onSuggestRequestDone(currentInputValue, payload.suggestNames, payload.suggestIDs, payload.suggestLocs, oThis.typeAheadObj.pEvent);
        })
        .setErrorHandler(function(response) {
          new Dialog()
            .setTitle(tx('sh:error-occurred'))
            .setBody(tx('su01'))
            .setButtons(Dialog.OK)
            .show();
        })
        .setMethod('GET')
        .setReadOnly(true)
        .send();
    }
  }


  this.onSuggestRequestDone = function(key, names, ids, locs, pEvent) {
    this.setCache(key, names, ids, locs);
    if (this.typeAheadObj.displaySuggestList(names, ids, locs)) {
      this.typeAheadObj.pEvent = pEvent;
      this.typeAheadObj.onListChange();
    }
  }

  this.getCache = function(key) {
    return this.suggestCache[key.toUpperCase()];
  }

  this.setCache = function(key, names, ids, locs) {
    this.suggestCache[key.toUpperCase()] = new Array(names, ids, locs);
  }

  this.init = function() {
    this.suggestURI = uri;
    this.suggestParam = param;
    this.suggestCache = [];
    this.networkType = networkType;
    if (!instructions) {
      instructions = tx('su02');
    }

    textBoxEl.value = q;
    this.typeAheadObj = new TypeAhead(rootEl, formEl, textBoxEl, idEl, defaultOptions, instructions, 0, successHandler, this.onInputChange, null, null, null, placeholderText, showNoMatches, override_resize);
  }

  var oThis = this;
  this.init();
}

function debug(str) {
  document.getElementById("debug").innerHTML += str + "<BR>";
}



  /**************  error_data.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/******************************************************************************\
|*                                                                            *|
|*  THIS FILE IS AUTOMATICALLY GENERATED, DO NOT MODIFY IT DIRECTLY!          *|
|*                                                                            *|
|*  Use /intern/errortool.php to modify or rebuild this file.                 *|
|*                                                                            *|
\******************************************************************************/

var
                                           noErr = 0,
                   kError_ErrorTool_BadErrorName = 1337001,
             kError_ErrorTool_DuplicateErrorName = 1337002,
               kError_ErrorTool_BadNamespaceName = 1337003,
                     kError_ErrorTool_BadErrorID = 1337004,
         kError_ErrorTool_DuplicateNamespaceName = 1337005,
                 kError_ErrorTool_BadNamespaceID = 1337006,
                    kError_ErrorTool_WriteFailed = 1337007,
                 kError_ErrorTool_BadServiceName = 1337008,
                  kError_ErrorTool_RequestFailed = 1337009,
                kError_ErrorTool_TempWriteFailed = 1337010,
                     kError_ErrorTool_LintFailed = 1337011,
                kError_Account_IncorrectPassword = 1340001,
                 kError_Account_NotAuthenticated = 1340002,
                  kError_Account_MissingPassword = 1340003,
                 kError_Profile_InvalidAttribute = 1341001,
                     kError_Database_WriteFailed = 1342001,
                      kError_Account_NotLoggedIn = 1340004,
                   kError_Global_ValidationError = 1346001,
                             kError_Mobile_Error = 1347001,
                          kError_Login_DownError = 1348001,
                 kError_Login_ExternalLoginError = 1348002,
                          kError_Login_NoCookies = 1348003,
                kError_Login_DeveloperLoginError = 1348004,
               kError_Login_ZiddioContestMessage = 1348005,
                 kError_Login_OneTimeCodeMessage = 1348006,
              kError_Login_MustLogInToSeeMessage = 1348007,
                     kError_Platform_NotLoggedIn = 1349001,
               kError_Platform_NoAppInfoForAppID = 1349002,
                      kError_Platform_LoginError = 1349003,
           kError_Login_ReactivateAccountMessage = 1348008,
                       kError_Login_GenericError = 1348009,
                kError_Login_CreatorAccountError = 1348010,
                  kError_Login_NotComfirmedError = 1348012,
            kError_Login_AccountDeactivatedError = 1348013,
                 kError_Login_AccountMergedError = 1348014,
                kError_Login_AccountMergingError = 1348015,
                           kError_TPS_NoTicketId = 1350001,
                  kError_TPS_InvalidTicketStatus = 1350002,
             kError_TPS_FailedUpdateTicketStatus = 1350003,
            kError_TPS_FailedUpdateTicketSubject = 1350004,
              kError_TPS_FailedUpdateTicketOwner = 1350005,
              kError_TPS_FailedUpdateTicketQueue = 1350006,
      kError_Login_IncorrectEmailOrPasswordError = 1348016,
     kError_Login_PasswordsCaseSensitiveSubError = 1348017,
                  kError_TPS_FailedCorrespondOut = 1350007,
                  kError_TPS_EmptyCorrespondence = 1350008,
                  kError_TPS_FailedTicketRefresh = 1350009,
                 kError_Registration_LoginViaReg = 1351001,
              kError_TPS_WarnUserFailedBadParams = 1350010,
                kError_TPS_WarnUserFailedBadCall = 1350011,
                kError_debategroups_alreadyVoted = 1352001,
              kError_Payment_CardAlreadyDisabled = 1353001,
                 kError_Payment_PaymentException = 1353002,
                   kError_Payment_InvalidRequest = 1353003,
                        kError_TPS_UserHasTicket = 1350013,
             kError_TPS_TicketAssociateBadParams = 1350014,
                kError_TPS_TicketAssociateFailed = 1350015,
                       kError_TPS_EmailHasTicket = 1350016,
                        kError_Level1_NotEnabled = 1354001,
          kError_Level1_CouldNotConnectToQueueDB = 1354002,
                 kError_Level1_QueueCommitFailed = 1354003,
            kError_Level1_TransactionBeginFailed = 1354004,
            kError_Level1_DirtyQueueSelectFailed = 1354005,
                       kError_Level1_NoDirtyKeys = 1354006,
            kError_Level1_DispatchCreationFailed = 1354007,
            kError_Level1_DirtyQueueUpdateFailed = 1354008,
           kError_Level1_TransactionCommitFailed = 1354009,
         kError_Level1_DispatchQueueSelectFailed = 1354010,
                 kError_Level1_NothingToDispatch = 1354011,
                    kError_TPS_FailedConfirmUser = 1350017,
                  kError_TPS_FailedResetPassword = 1350018,
                 kError_TPS_UnknownSimpleCommand = 1350019,
                     kError_TPS_NameChangeFailed = 1350020,
                      kError_TPS_InvalidBdayDate = 1350021,
              kError_TPS_InvalidBdayUserTooYoung = 1350022,
                kError_TPS_InvalidBdayUserTooOld = 1350023,
             kError_TPS_BdayChangeGeneralFailure = 1350024,
           kError_TPS_TicketAssociateMergeFailed = 1350025,
          kError_TPS_TicketAssociateSimpleFailed = 1350026,
      kError_TPS_TicketAssociateUnspecifiedError = 1350027,
       kError_TPS_TicketAssociateRemoveUIDFailed = 1350028,
        kError_TPS_VerificationScoreUpdateFailed = 1350029,
                     kError_TPS_AffilAddUseReAdd = 1350030,
                kError_TPS_AffilAddEmailRequired = 1350031,
                       kError_TPS_AffilAddFailed = 1350032,
                   kError_TPS_AffilConfirmFailed = 1350033,
                    kError_TPS_AffilRemoveFailed = 1350034,
                   kError_TPS_AffilPendingFailed = 1350035,
                    kError_TPS_AffilReaddFailure = 1350036,
                    kError_TPS_AffilsUpdateError = 1350037,
             kError_TPS_AffilWidgetUnknownAction = 1350038,
              kError_TPS_AccountChangeFailedDark = 1350039,
                     kError_Chat_SendPermissions = 1356001,
                        kError_Chat_NotAvailable = 1356002,
               kError_Chat_SendOtherNotAvailable = 1356003,
                             kError_Chat_Unknown = 1356004,
                        kError_Async_NotLoggedIn = 1357001,
                      kError_Async_NotInternUser = 1357002,
                kError_TPS_TicketAttachBadParams = 1350040,
         kError_TPS_TicketAttachGetPendingFailed = 1350041,
                      kError_Chat_MessageTooLong = 1356005,
              kError_Payment_RefundExceedsAmount = 1353004,
         kError_Payment_RefundAmountNotSupported = 1353005,
                    kError_Database_DatabaseDown = 1342002,
                 kError_TPS_AffilAddHSUserTooOld = 1350042,
                   kError_Admanager_ActionFailed = 1359001,
                   kError_Admanager_UpdateFailed = 1359002,
              kError_Calendar_LackEditPermission = 1360001,
                    kError_Calendar_GenericError = 1360002,
                            kError_CSDC_Disabled = 1361001,
               kError_Calendar_CannotJoinPrivate = 1360003,
                      kError_Reviews_WriteFailed = 1362001,
                     kError_Global_FailedCaptcha = 1346002,
              kError_Payment_RefundMerchantCheck = 1353006,
                          kError_Video_TagExists = 1363001,
                          kError_Video_TagFailed = 1363002,
                    kError_Video_TagLimitReached = 1363003,
                   kError_Calendar_CannotSeeItem = 1360004,
                 kError_Calendar_PrivateCalendar = 1360005,
                       kError_Async_LoginChanged = 1357003,
              kError_Calendar_CannotInviteOthers = 1360006,
             kError_Mobile_CarrierInputDuplicate = 1347002,
                            kError_Mobile_NoData = 1347003,
            kError_Ratings_MissingRequiredParams = 1365001,
                   kError_Ratings_InvalidContest = 1365002,
                    kError_Ratings_InvalidTarget = 1365003,
                kError_Ratings_ContestNotRunning = 1365004,
                   kError_Ratings_NoTargetsFound = 1365005,
                     kError_Ratings_TargetTrojan = 1365006,
                     kError_Ratings_InvalidScore = 1365007,
                    kError_TPS_TicketAddCCFailed = 1350043,
                 kError_TPS_TicketRemoveCCFailed = 1350044,
                     kError_TPS_QueueAddCCFailed = 1350045,
                  kError_TPS_QueueRemoveCCFailed = 1350046,
                            kError_TPS_NoQueueId = 1350047,
              kError_TPS_CCEditNoActionSpecified = 1350048,
                      kError_Global_ContentError = 1346003,
              kError_Mobile_StatusUpdatesPrivacy = 1347004,
                      kError_Chat_MessageBlocked = 1356006,
                 kError_TPS_FailedChangeLanguage = 1350049,
                kError_TPS_QueuePrefChangeFailed = 1350050,
                 kError_TPS_FailedChangePriority = 1350051,
                  kError_Chat_DownForMaintenance = 1356007,
                    kError_Async_CSRFCheckFailed = 1357004,
                   kError_Async_ParameterFailure = 1357005,
                         kError_Calendar_Blocked = 1360007,
            kError_Video_AcceptedUploadAgreement = 1363004,
                   kError_Database_CannotConnect = 1342003,
                     kError_Photos_CommentFailed = 1366001,
                     kError_Async_BadPermissions = 1357006,
                         kError_Wall_PostFailure = 1367001,
                   kError_Example_DivisionByZero = 1370001,
          kError_Typeahead_StaticSourceListEmpty = 1371001,
                           kError_Global_CantSee = 1346004,
                     kError_Chat_TooManyMessages = 1356008,
                     kError_Account_KarmaBlocked = 1340005,
                  kError_Platform_InvalidRequest = 1349004,
               kError_Platform_AppNotOwnedByUser = 1349005,
               kError_Platform_NoFriendsSelected = 1349006,
       kError_Platform_CallbackValidationFailure = 1349007,
      kError_Platform_ApplicationResponseInvalid = 1349008,
          kError_Platform_TestConsoleKarmaWarned = 1349009,
                     kError_FBPages_TooManyAdded = 1373001,
               kError_FBPages_AddFanStatusFailed = 1373002,
            kError_FBPages_RemoveFanStatusFailed = 1373003,
               kError_FBPages_EditSettingsFailed = 1373004,
                   kError_Minifeed_HideClickFail = 1375001,
                     kError_Group_NotGroupMember = 1376001,
                       kError_Group_UnableToJoin = 1376002,
                       kError_Group_NoPermission = 1376003,
                  kError_Group_EmptyOfficerTitle = 1376004,
                  kError_Group_UnableEditOfficer = 1376005,
               kError_Notes_InvalidDeleteRequest = 1377001,
                       kError_Notes_DeleteFailed = 1377002,
                      kError_Notes_NoAccessRight = 1377003,
                       kError_Notes_FailToAddTag = 1377004,
                         kError_Notes_NoSuchNote = 1377005,
                      kError_Notes_UnknownAction = 1377006,
               kError_Notes_NotebookUpdateFailed = 1377007,
                          kError_TPS_CRBadParams = 1350052,
                  kError_TPS_CRUnspecifiedAction = 1350053,
                   kError_TPS_CRUnspecifiedError = 1350054,
                        kError_TPS_CRInsuffPrivs = 1350055,
                    kError_TPS_CRDataFetchFailed = 1350056,
                       kError_TPS_CRCreateFailed = 1350057,
             kError_TPS_CRCollectionCreateFailed = 1350058,
                       kError_TPS_CRUpdateFailed = 1350059,
             kError_TPS_CRCollectionUpdateFailed = 1350060,
                   kError_TPS_CRBodyUpdateFailed = 1350061,
                   kError_TPS_CRRemoveBodyFailed = 1350062,
            kError_Marketplace_MessageSendFailed = 1378001,
                    kError_TPS_CRFetchBodyFailed = 1350063,
               kError_TPS_CRFetchBodyTypesFailed = 1350064,
                        kError_TPS_CRFetchFailed = 1350065,
               kError_Notes_UnknownUploadCommand = 1377008,
              kError_RichMediaContent_NoMoreFBML = 1380001,
          kError_RichMediaContent_AddFBMLFailure = 1380002,
            kError_RichMediaContent_GenericError = 1380003,
            kError_TPS_TraccampBugCreationFailed = 1350066,
                  kError_TPS_NoTraccampProjectId = 1350067,
             kError_PlatformRequests_NoSelection = 1381001,
           kError_PlatformRequests_OutOfRequests = 1381002,
             kError_RichMediaContent_NoMoreFlash = 1380004,
         kError_RichMediaContent_AddFlashFailure = 1380005,
                    kError_Queues_UnknownCommand = 1382001,
           kError_TPS_CRUpdateFrequentCollFailed = 1350068,
           kError_TPS_CRDeleteFrequentCollFailed = 1350069,
              kError_TPS_CRUpdateInitialCRFailed = 1350070,
              kError_TPS_CRDeleteInitialCRFailed = 1350071,
                  kError_FBPages_BlockUserFailed = 1373005,
                          kError_Reviews_TooLong = 1362003,
            kError_Reviews_MissingRequiredFields = 1362004,
                     kError_Reviews_DeleteFailed = 1362005,
              kError_FBPayments_InvalidParamters = 1383001,
           kError_FBPayments_UnableToCreateOrder = 1383002,
                  kError_TPS_CROrderUpdateFailed = 1350072,
                   kError_Maps_DeskDeleteFailure = 1384001,
                   kError_Maps_DeskAssignFailure = 1384002,
                   kError_Maps_DeskRotateFailure = 1384003,
                   kError_Maps_DeskCreateFailure = 1384004,
                     kError_Maps_DeskMoveFailure = 1384005,
                         kError_Mobile_InvalidIP = 1347005,
              kError_Bookmarks_AddBookmarkFailed = 1385001,
           kError_Bookmarks_RemoveBookmarkFailed = 1385002,
                       kError_TPS_CRDeleteFailed = 1350073,
                kError_TPS_CRDeleteFailedSpecial = 1350074,
             kError_TPS_CRDeleteCollectionFailed = 1350075,
      kError_TPS_CRDeleteCollectionFailedSpecial = 1350076;



  /**************  lib/ui/animation.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  @provides animation
 */

/**
 *
 *  A lovely library which animates random stuff. The most basic functions you need to
 *  know are .to(), .go(), and .duration(). To animate an element from its current width
 *  and height to 100px \ 100px over 1 second, you'd do something like this:
 *  animation(obj).to('width', 100).to('height', 100).duration(1000).go();
 *
 *  Getting into the slightly more advanced functions, you'll find .from(), .by(),
 *  .show(), and .hide(). To animate width from 100px to 200px, increase height by
 *  100, and hide at the end of the animation (an awfully awkward animation), you'd do
 *  this:
 *  animation(obj).from('width', 100).to('width', 200).by('height', 100).hide().go();
 *
 *  And the functions with the steepest (but still pretty easy) learning curve are .blind()
 *  and .checkpoint(). blind will essentially create another element inside your element
 *  with an explicity set width and height (which is "intelligently" set). This is useful
 *  when you're animating width either from or to 0px and you want to avoid the text
 *  continually wrapping to fit its parent. You might have to see it in action to
 *  understand. checkpoint will checkpoint the animation you've built and start a new
 *  one after the current one is done.
 *  So... to take a div and shrink it up into nothingness over 1 second, and then reopen
 *  to an explicit width and height of 400px, try this:
 *  animation(obj).to('width', 0).to('height', 0).blind().duration(1000).checkpoint()
 *                .to('width', 400).to('height', 400).blind().duration(1000).go();
 *
 *  checkpoint can also take a parameter between 0 and 1 for distance. A distance of
 *  0.5 means your next animation will actually start halfway through the first animation.
 *
 *  You can also apply an easing function to your animation which essentially just affects
 *  the rate at which your animation occurs. For instance if you want an animation to start
 *  slowly and speed up at the end, try animation(this).ease(animation.ease.both)
 *
 */

//
// Animation. It animates things.
function animation(obj) {
  // Sanity check that request is valid
  if (obj == undefined) {
    Util.error("Creating animation on non-existant object");
    return;
  }
  if (this == window) {
    return new animation(obj);
  } else {
    this.obj = obj;
    this._reset_state();
    this.queue = [];
    this.last_attr = null;
  }
}
animation.resolution = 20;
animation.offset = 0;

// Initializes the state to blank values
animation.prototype._reset_state = function() {
  this.state = {
    attrs: {},
    duration: 500 // default duration
  }
}

// Stops any current animation
animation.prototype.stop = function() {
  this._reset_state();
  this.queue = [];
  return this;
}

// Builds an overflow:hidden container for this.obj. Used with .blind()
animation.prototype._build_container = function() {
  if (this.container_div) {
    this._refresh_container();
    return;
  }
  // ref-counting here on the magic container in case someone decides to start two animations with blind() on the same element
  // Either way it's not ideal because if animating to 'auto' the calculations will probably be incorrect... but at least this
  // way it won't tear up your DOM.
  if (this.obj.firstChild && this.obj.firstChild.__animation_refs) {
    this.container_div = this.obj.firstChild;
    this.container_div.__animation_refs++;
    this._refresh_container();
    return;
  }
  var container = document.createElement('div');
  container.style.padding = '0px';
  container.style.margin = '0px';
  container.style.border = '0px';
  container.__animation_refs = 1;
  var children = this.obj.childNodes;
  while (children.length) {
    container.appendChild(children[0]);
  }
  this.obj.appendChild(container);
  this.obj.style.overflow = 'hidden';
  this.container_div = container;
  this._refresh_container();
}

// Refreshes the size of the container. Used on checkpoints and such.
animation.prototype._refresh_container = function() {
  this.container_div.style.height = 'auto';
  this.container_div.style.width = 'auto';
  this.container_div.style.height = this.container_div.offsetHeight+'px';
  this.container_div.style.width = this.container_div.offsetWidth+'px';
}

// Destroys the container built by _build_container()
animation.prototype._destroy_container = function() {
  if (!this.container_div) {
    return;
  }
  if (!--this.container_div.__animation_refs) {
    var children = this.container_div.childNodes;
    while (children.length) {
      this.obj.appendChild(children[0]);
    }
    this.obj.removeChild(this.container_div);
  }
  this.container_div = null;
}

// Generalized attr function. Calls to .to, .by, and .from go through this
animation.ATTR_TO = 1;
animation.ATTR_BY = 2;
animation.ATTR_FROM = 3;
animation.prototype._attr = function(attr, value, mode) {

  // Turn stuff like border-left into borderLeft
  attr = attr.replace(/-[a-z]/gi, function(l) {
    return l.substring(1).toUpperCase();
  });

  var auto = false;
  switch (attr) {
    case 'background':
      this._attr('backgroundColor', value, mode);
      return this;

    case 'margin':
      value = animation.parse_group(value);
      this._attr('marginBottom', value[0], mode);
      this._attr('marginLeft', value[1], mode);
      this._attr('marginRight', value[2], mode);
      this._attr('marginTop', value[3], mode);
      return this;

    case 'padding':
      value = animation.parse_group(value);
      this._attr('paddingBottom', value[0], mode);
      this._attr('paddingLeft', value[1], mode);
      this._attr('paddingRight', value[2], mode);
      this._attr('paddingTop', value[3], mode);
      return this;

    case 'backgroundColor':
    case 'borderColor':
    case 'color':
      value = animation.parse_color(value);
      break;

    case 'opacity':
      value = parseFloat(value, 10);
      break;

    case 'height':
    case 'width':
      if (value == 'auto') {
        auto = true;
      } else {
        value = parseInt(value, 10);
      }
      break;

    case 'borderWidth':
    case 'lineHeight':
    case 'fontSize':
    case 'marginBottom':
    case 'marginLeft':
    case 'marginRight':
    case 'marginTop':
    case 'paddingBottom':
    case 'paddingLeft':
    case 'paddingRight':
    case 'paddingTop':
    case 'bottom':
    case 'left':
    case 'right':
    case 'top':
    case 'scrollTop':
    case 'scrollLeft':
      value = parseInt(value, 10);
      break;

    default:
      throw new Error(attr+' is not a supported attribute!');
  }

  if (this.state.attrs[attr] === undefined) {
    this.state.attrs[attr] = {};
  }
  if (auto) {
    this.state.attrs[attr].auto = true;
  }
  switch (mode) {
    case animation.ATTR_FROM:
      this.state.attrs[attr].start = value;
      break;

    case animation.ATTR_BY:
      this.state.attrs[attr].by = true;
      // fall through

    case animation.ATTR_TO:
      this.state.attrs[attr].value = value;
      break;
  }
}

// Explcit animation to a certain value
animation.prototype.to = function(attr, value) {
  if (value === undefined) {
    this._attr(this.last_attr, attr, animation.ATTR_TO);
  } else {
    this._attr(attr, value, animation.ATTR_TO);
    this.last_attr = attr;
  }
  return this;
}

// Animation by a value (i.e. add this value to the current value)
animation.prototype.by = function(attr, value) {
  if (value === undefined) {
    this._attr(this.last_attr, attr, animation.ATTR_BY);
  } else {
    this._attr(attr, value, animation.ATTR_BY);
    this.last_attr = attr;
  }
  return this;
}

// Start the animation from a value instead of the current value
animation.prototype.from = function(attr, value) {
  if (value === undefined) {
    this._attr(this.last_attr, attr, animation.ATTR_FROM);
  } else {
    this._attr(attr, value, animation.ATTR_FROM);
    this.last_attr = attr;
  }
  return this;
}

// How long is this animation supposed to last (in miliseconds)
animation.prototype.duration = function(duration) {
  this.state.duration = duration ? duration : 0;
  return this;
}

// Checkpoint the animation to start a new one.
animation.prototype.checkpoint = function(distance /* = 1.0 */, callback) {
  if (distance === undefined) {
    distance = 1;
  }
  this.state.checkpoint = distance;
  this.queue.push(this.state);
  this._reset_state();
  this.state.checkpointcb = callback;
  return this;
}

// This animation requires an overflow container (usually used for width animations)
animation.prototype.blind = function() {
  this.state.blind = true;
  return this;
}

// Hide this object at the end of the animation
animation.prototype.hide = function() {
  this.state.hide = true;
  return this;
}

// Show this object at the beginning of the animation
animation.prototype.show = function() {
  this.state.show = true;
  return this;
}

// Use an easing function to adjust the distribution of the animation state over frames
animation.prototype.ease = function(ease) {
  this.state.ease = ease;
  return this;
}

// Let the animation begin!
animation.prototype.go = function() {
  var time = (new Date()).getTime();
  this.queue.push(this.state);

  for (var i = 0; i < this.queue.length; i++) {
    this.queue[i].start = time - animation.offset;
    if (this.queue[i].checkpoint) {
      time += this.queue[i].checkpoint * this.queue[i].duration;
    }
  }
  animation.push(this);
  return this;
}

// Draw a frame for this animation
animation.prototype._frame = function(time) {
  var done = true;
  var still_needs_container = false;
  var whacky_firefox = false;
  for (var i = 0; i < this.queue.length; i++) {

    // If this animation shouldn't start yet we can abort early
    var cur = this.queue[i];
    if (cur.start > time) {
      done = false;
      continue;
    }

    if (cur.checkpointcb) {
      this._callback(cur.checkpointcb, time - cur.start);
      cur.checkpointcb = null;
    }

    // We need to initialize an animation on the first frame
    if (cur.started === undefined) {
      if (cur.show) {
        this.obj.style.display = 'block';
      }
      for (var a in cur.attrs) {
        if (cur.attrs[a].start !== undefined) {
          continue;
        }
        switch (a) {
          case 'backgroundColor':
          case 'borderColor':
          case 'color':
            // Defer to the left border color, whatever.
            var val = animation.parse_color(get_style(this.obj, a == 'borderColor' ? 'borderLeftColor' : a));

            // I'm not sure why anyone would want to do relative color adjustment... but at least they can
            if (cur.attrs[a].by) {
              cur.attrs[a].value[0] = Math.min(255, Math.max(0, cur.attrs[a].value[0] + val[0]));
              cur.attrs[a].value[1] = Math.min(255, Math.max(0, cur.attrs[a].value[1] + val[1]));
              cur.attrs[a].value[2] = Math.min(255, Math.max(0, cur.attrs[a].value[2] + val[2]));
            }
            break;

          case 'opacity':
            var val = get_opacity(this.obj);
            if (cur.attrs[a].by) {
              cur.attrs[a].value = Math.min(1, Math.max(0, cur.attrs[a].value + val));
            }
            break;

          case 'height':
          case 'width':
            var val = animation['get_'+a](this.obj);
            if (cur.attrs[a].by) {
              cur.attrs[a].value += val;
            }
            break;

          case 'scrollLeft':
          case 'scrollTop':
            var val = (this.obj == document.body) ? (document.documentElement[a] || document.body[a]) : this.obj[a];
            if (cur.attrs[a].by) {
              cur.attrs[a].value += val;
            }
            cur['last'+a] = val;
            break;

          default:
            var val = parseInt(get_style(this.obj, a), 10);
            if (cur.attrs[a].by) {
              cur.attrs[a].value += val;
            }
            break;
        }
        cur.attrs[a].start = val;
      }

      // If we're animating height or width to "auto" we need to do some DOM-fu to figure out what that means in px
      if ((cur.attrs.height && cur.attrs.height.auto) ||
          (cur.attrs.width && cur.attrs.width.auto)) {

        
        // In all browsers that aren't Firefox the div will stay solid red. In Firefox it flickers. The workaround
        // for the bug is included in the code block above, but commented out.
        if (ua.firefox() < 3) {
          whacky_firefox = true;
        }

        // Set any attributes that affect element size to their final desired values
        this._destroy_container();
        for (var a in {height: 1, width: 1,
                       fontSize: 1,
                       borderLeftWidth: 1, borderRightWidth: 1, borderTopWidth: 1, borderBottomWidth: 1,
                       paddingLeft: 1, paddingRight: 1, paddingTop: 1, paddingBottom: 1}) {
          if (cur.attrs[a]) {
            this.obj.style[a] = cur.attrs[a].value + (typeof cur.attrs[a].value == 'number' ? 'px' : '');
          }
        }

        // Record the dimensions of what the element will look like after the animation
        if (cur.attrs.height && cur.attrs.height.auto) {
          cur.attrs.height.value = animation.get_height(this.obj);
        }
        if (cur.attrs.width && cur.attrs.width.auto) {
          cur.attrs.width.value = animation.get_width(this.obj);
        }

        // We don't need to do anything else with temporarily adjusted style because they're
        // about to be overwritten in the frame loop below
      }

      cur.started = true;
      if (cur.blind) {
        this._build_container();
      }
    }

    // Calculate the animation's progress from 0 - 1
    var p = (time - cur.start) / cur.duration;
    if (p >= 1) {
      p = 1;
      if (cur.hide) {
        this.obj.style.display = 'none';
      }
    } else {
      done = false;
    }
    var pc = cur.ease ? cur.ease(p) : p;

    // If this needs a blind container and doesn't have one, we build it
    if (!still_needs_container && p != 1 && cur.blind) {
      still_needs_container = true;
    }

    // Hack documented above
    if (whacky_firefox && this.obj.parentNode) {
      var parentNode = this.obj.parentNode;
      var nextChild = this.obj.nextSibling;
      parentNode.removeChild(this.obj);
    }

    // Loop through each animated attribute and set it where it needs to be
    for (var a in cur.attrs) {
      switch (a) {
        case 'backgroundColor':
        case 'borderColor':
        case 'color':
          this.obj.style[a] = 'rgb('+
            animation.calc_tween(pc, cur.attrs[a].start[0], cur.attrs[a].value[0], true)+','+
            animation.calc_tween(pc, cur.attrs[a].start[1], cur.attrs[a].value[1], true)+','+
            animation.calc_tween(pc, cur.attrs[a].start[2], cur.attrs[a].value[2], true)+')';
          break;

        case 'opacity':
          set_opacity(this.obj, animation.calc_tween(pc, cur.attrs[a].start, cur.attrs[a].value));
          break;

        case 'height':
        case 'width':
          this.obj.style[a] = pc == 1 && cur.attrs[a].auto ? 'auto' :
                              animation.calc_tween(pc, cur.attrs[a].start, cur.attrs[a].value, true)+'px';
          break;

        case 'scrollLeft':
        case 'scrollTop':
          // Special-case here for scrolling. If the user overrides the scroll we immediately terminate this animation
          var val = (this.obj == document.body) ? (document.documentElement[a] || document.body[a]) : this.obj[a];
          if (cur['last'+a] != val) {
            delete cur.attrs[a];
          } else {
            var diff = animation.calc_tween(pc, cur.attrs[a].start, cur.attrs[a].value, true) - val;
            if (a == 'scrollLeft') {
              window.scrollBy(diff, 0);
            } else {
              window.scrollBy(0, diff);
            }
            cur['last'+a] = diff + val;
          }
          break;

        default:
          this.obj.style[a] = animation.calc_tween(pc, cur.attrs[a].start, cur.attrs[a].value, true)+'px';
          break;
      }
    }

    // If this animation is complete remove it from the queue
    if (p == 1) {
      this.queue.splice(i--, 1);
      this._callback(cur.ondone, time - cur.start - cur.duration);
    }
  }

  // Hack documented above
  if (whacky_firefox) {
    parentNode[nextChild ? 'insertBefore' : 'appendChild'](this.obj, nextChild);
  }

  if (!still_needs_container && this.container_div) {
    this._destroy_container();
  }
  return !done;
}

// Add a callback to fire when this animation is finished
animation.prototype.ondone = function(fn) {
  this.state.ondone = fn;
  return this;
}

// Call a callback with a time offset (for instantiating more animations)
animation.prototype._callback = function(callback, offset) {
  if (callback) {
    animation.offset = offset;
    callback.call(this);
    animation.offset = 0;
  }
}

// Calculates a value in between two values based on a percentage. Basically a weighted average.
animation.calc_tween = function(p, v1, v2, whole) {
  return (whole ? parseInt : parseFloat)((v2 - v1) * p + v1, 10);
}

// Takes a color like #fff and returns an array of [255, 255, 255].
animation.parse_color = function(color) {
  var hex = /^#([a-f0-9]{1,2})([a-f0-9]{1,2})([a-f0-9]{1,2})$/i.exec(color);
  if (hex) {
    return [parseInt(hex[1].length == 1 ? hex[1] + hex[1] : hex[1], 16),
            parseInt(hex[2].length == 1 ? hex[2] + hex[2] : hex[2], 16),
            parseInt(hex[3].length == 1 ? hex[3] + hex[3] : hex[3], 16)];
  } else {
    var rgb = /^rgba? *\(([0-9]+), *([0-9]+), *([0-9]+)(?:, *([0-9]+))?\)$/.exec(color);
    if (rgb) {
      if (rgb[4] === '0') {
        return [255, 255, 255]; // transparent
      } else {
        return [parseInt(rgb[1], 10), parseInt(rgb[2], 10), parseInt(rgb[3], 10)];
      }
    } else if (color == 'transparent') {
      return [255, 255, 255]; // not much we can do here...
    } else {
      // When we open this to Platform we'll need a key-value list of names to rgb values
      throw 'Named color attributes are not supported.';
    }
  }
}

// Takes a CSS attribute like padding or margin and returns an explicit array of 4 values
// Ex: '0px 1px' -> ['0px', '1px', '0px', '1px']
animation.parse_group = function(value) {
  var value = trim(value).split(/ +/);
  if (value.length == 4) {
    return value;
  } else if (value.length == 3) {
    return [value[0], value[1], value[2], value[1]];
  } else if (value.length == 2) {
    return [value[0], value[1], value[0], value[1]];
  } else {
    return [value[0], value[0], value[0], value[0]];
  }
}

// Gets the current height of an element which when used with obj.style.height = height+'px' is a visual NO-OP
animation.get_height = function(obj) {
  var pT = parseInt(get_style(obj, 'paddingTop'), 10),
      pB = parseInt(get_style(obj, 'paddingBottom'), 10),
      bT = parseInt(get_style(obj, 'borderTopWidth'), 10),
      bW = parseInt(get_style(obj, 'borderBottomWidth'), 10);
  return obj.offsetHeight - (pT ? pT : 0) - (pB ? pB : 0) - (bT ? bT : 0) - (bW ? bW : 0);
}

// Similar to get_height except for widths
animation.get_width = function(obj) {
  var pL = parseInt(get_style(obj, 'paddingLeft'), 10),
      pR = parseInt(get_style(obj, 'paddingRight'), 10),
      bL = parseInt(get_style(obj, 'borderLeftWidth'), 10),
      bR = parseInt(get_style(obj, 'borderRightWidth'), 10);
  return obj.offsetWidth - (pL ? pL : 0) - (pR ? pR : 0) - (bL ? bL : 0) - (bR ? bR : 0);
}

// Add this animation object to the global animation stack.
animation.push = function(instance) {
  if (!animation.active) {
    animation.active = [];
  }
  animation.active.push(instance);
  if (!animation.timeout) {
    animation.timeout = setInterval(animation.animate.bind(animation), animation.resolution);
  }
  animation.animate(true);
}

// Renders a frame from each animation currently active. By putting all our animations in one
// stack it gives us the advantage of a single setInterval with all style updates in a single
// callback. That means the browser will do less rendering and multiple animations will be
// smoother.
animation.animate = function(last) {
  var done = true;
  var time = (new Date()).getTime();
  for (var i = last === true ? animation.active.length - 1 : 0; i < animation.active.length; i++) {
    if (animation.active[i]._frame(time)) {
      done = false;
    } else {
      animation.active.splice(i--, 1); // remove from the list
    }
  }
  if (done) {
    clearInterval(animation.timeout);
    animation.timeout = null;
  }
}

// Ease functions. These functions all have a domain and (maybe) range of 0 - 1
animation.ease = {}
animation.ease.begin = function(p) {
  return p * p;
}
animation.ease.end = function(p) {
  p -= 1;
  return -(p * p) + 1;
}
animation.ease.both = function(p) {
  if (p <= 0.5) {
    return (p * p) * 2;
  } else {
    p -= 1;
    return (p * p) * -2 + 1;
  }
}



  /**************  lib/ui/dialog.js  ************//******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/

/**
 *  Class for creating pop-up dialog boxes.  For sample code, check out:
 *
 *    - http://www.dev.facebook.com/intern/example/dialog
 *    - html/intern/example/dialog/javascript.js
 *
 *  There are two (compatible) ways to create dialogs: you can set their
 *  content directly from JavaScript, using various setXXX methods, like:
 *
 *    new Dialog()
 *      .setTitle('This is the title')
 *      .setBody('This is the body')
 *      .setButtons(Dialog.OK_AND_CANCEL)
 *      .show();
 *
 *  or you can have the content set in response to an AsyncRequest using
 *  the setAsync method:
 *
 *    var async = new AsyncRequest().setURI(uri);
 *    new Dialog.setAsync(async).show();
 *
 *  where uri is an endpoint that uses DialogResponse, e.g.:
 *
 *    $response = new DialogReponse();
 *    $response->setTitle('Title')
 *             ->setBody('body')
 *             ->setButtons(array(DialogResponse::OK, DialogResponse::CANCEL))
 *             ->send();
 *
 *  You can also set the handler for the dialog in a few different ways:
 *
 *    1) Call dialog.setHandler(f), where f is a function that takes a
 *       button object.
 *    2) Call dialog.setPostURI(uri), to make the contents of form fields
 *       in the dialog get posted asynchronously to a URI when the user
 *       clicks a button.
 *    3) Create a custom button with a 'handler' property.
 *
 *  NOTE: currently, the Dialog class is just a wrapper around dialogpro.js,
 *        but providing a much nicer interface.  Over time, we'll make
 *        the Dialog class's implementation standalone, and port existing
 *        dialogs over to using it.
 *
 *  (also requires key_event_controller.js -- TODO: make that library provide)
 *
 *  @author jrosenstein
 *  @provides dialog
 *  @requires util dom event-extensions array-extensions intl
 *
 */
function /* class */ Dialog() {
  Dialog._setup();
  this._pd = new pop_dialog();
  this._pd._dialog_object = this;
}

Dialog.OK = {
  name : 'ok',
  label : tx('sh:ok-button')
};
Dialog.CANCEL = {
  name : 'cancel',
  label : tx('sh:cancel-button'),
  className : 'inputaux'
};
Dialog.CLOSE = {
  name : 'close',
  label : tx('sh:close-button')
};
Dialog.SAVE = {
  name : 'save',
  label : tx('sh:save-button')
};
Dialog.OK_AND_CANCEL = [Dialog.OK, Dialog.CANCEL];
Dialog._STANDARD_BUTTONS = [Dialog.OK, Dialog.CANCEL, Dialog.CLOSE, Dialog.SAVE];

Dialog.getCurrent = function() {
  var stack = generic_dialog.dialog_stack;
  if (stack.length == 0) {
    return null;
  }
  return stack[stack.length - 1]._dialog_object || null;
};

Dialog._basicMutator = function(private_key) {
  return function(value) {
    this[private_key] = value;
    this._dirty();
    return this;
  };
};

copy_properties(Dialog.prototype, {

  /**
   * Construct/display the dialog, typically after you've set all of its
   * properties via setXXX methods.
   */
  show : function() {
    this._showing = true;
    this._dirty();
    return this;
  },

  /**
   * Destroy this dialog (fading it out from view).
   */
  hide : function() {
    this._showing = false;
    if (this._autohide_timeout) {
      clearTimeout(this._autohide_timeout);
      this._autohide_timeout = null;
    }
    this._pd.fade_out(250);
    return this;
  },

  /**
   * Set the HTML to appear in the title area of the dialog (blue bar
   * along the top).
   */
  setTitle : Dialog._basicMutator('_title'),

  /**
   * Set the HTML to appear in the main white area of the dialog.
   */
  setBody : Dialog._basicMutator('_body'),

  /**
   * Set the timeout to auto-fade the dialog
   */
  setAutohide : function(autohide) {
    if (autohide) {
      if (this._showing) {
        this._autohide_timeout = setTimeout(bind(this, 'hide'), autohide);
      } else {
        this._autohide = autohide;
      }
    } else {
      this._autohide = null;
      if (this._autohide_timeout) {
        clearTimeout(this._autohide_timeout);
        this._autohide_timeout = null;
      }
    }
    return this;
  },

  /**
   * Set the HTML to appear in the space above the body.
   */
  setSummary : Dialog._basicMutator('_summary'),

  /**
   * Specify which buttons should appear in the lower right corner of the
   * dialog.  You can pass in either a single button or an array of buttons.
   *
   * Typically, you can just use the standard dialog buttons, i.e. one of:
   *   dialog.setButtons(Dialog.OK)
   *   dialog.setButtons(Dialog.CANCEL)
   *   dialog.setButtons(Dialog.OK_AND_CANCEL)
   *   dialog.setButtons(Dialog.CLOSE)
   *
   * Or you can specify your own custom "button objects", which look like:
   *
   *   {
   *     name: 'help',   // to be used as the name attribute of the button
   *     label: 'Help',  // user-visible string
   *     className: '',  // optional, if you want to style the button
   *     handler: function(button) { ... }   // optional
   *   }
   *
   * If you do specify a handler, it will be called when the button is pressed,
   * before hiding the dialog box.  If you don't want the dialog box to
   * disappear, just have your handler return false.
   */
  setButtons : function(buttons) {
    if (!(buttons instanceof Array)) {
      buttons = [buttons];
    }

    for (var i = 0; i < buttons.length; ++i) {
      if (typeof(buttons[i]) == 'string') {
        var button = Dialog._findButton(Dialog._STANDARD_BUTTONS, buttons[i]);
        if (!button) {
          Util.error('Unknown button: ' + buttons[i]);
        }
        buttons[i] = button;
      }
    }

    this._buttons = buttons;
    this._dirty();
    return this;
  },

  /**
   * Set the HTML that appears on the left side of the button area (i.e. the
   * lower-left corner) of this dialog.
   */
  setButtonsMessage : Dialog._basicMutator('_buttons_message'),

  /**
   * If set to true, then, if another dialog is created before this one has
   * been hidden, then this one will be resurrected after the new one is
   * hidden.
   */
  setStackable : Dialog._basicMutator('_is_stackable'),

  /**
   * Set the function to be called when the user clicks any button on the
   * dialog other than Cancel.  The function will be passed one argument:
   * the button object for the button that was clicked, which in most cases
   * will be Dialog.OK.
   */
  setHandler : function(handler) {
    this._handler = handler;
    return this;
  },

  /**
   * setPostURI is an alternative to setHandler.  It specifies that, when the
   * user clicks a button other than Cancel, we should fire off an AsyncRequest
   * to post_uri, with method POST, and with data set to name/value pairs of
   * all form fields in the dialog box (including the button that was clicked).
   *
   * The post_uri endpoint can, in turn, send back a payload (via DialogResponse)
   * that can modify the dialog.  Any attributes not specfied in the payload
   * (via a DialogResponse::setXXX method) will remain the same.  If you'd
   * like the dialog to close, call DialogResponse::hide or ::setAutohide.
   *
   * In this way, you can achieve complex back-and-forth workflows.  Note that
   * your close handler (if you set one with setCloseHandler) will be called
   * only at the end of the workflow -- different steps of the workflow do not
   * constitute different dialogs.
   */
  setPostURI : function(post_uri) {
    this.setHandler(this._submitForm.bind(this, 'POST', post_uri));
    return this;
  },

   /*
    * Similar to setPostURI, only that the AsyncRequest is fired off with method GET
    */
  setGetURI : function(get_uri) {
    this.setHandler(this._submitForm.bind(this, 'GET', get_uri));
    return this;
  },

  /**
   * Set whether this dialog is "modal", i.e. whether the user can click on
   * other things in the page while the dialog is visible.
   */
  setModal : function(modal /* = true */) {
    if (modal === undefined) {
      modal = true;
    }

    if (this._showing && this._modal && !modal) {
      Util.error("At the moment we don't support un-modal-ing a modal dialog");
    }

    this._modal = modal;
    return this;
  },

  /**
   * Adjusts the width of the entire dialog box so as to make the width of
   * the body section -- not including padding or border -- equal to width,
   * which is measured in pixels.
   */
  setContentWidth : function(width) {
    this._content_width = width;
    this._dirty();
    return this;
  },

  /**
   * Adds the className to the underlying dialog.
   * If you need to change the width, use setContentWidth. You should
   * probably NOT use this method unless you cannot find any other way of
   * achieving the styling of the Dialog.
   */
  setClassName : Dialog._basicMutator('_class_name'),

  /**
   * Set the function to be called when the dialog disappears, either as the
   * result of the user clicking a button (including Cancel), or another dialog
   * being created (if this dialog is not stackable).
   */
  setCloseHandler : function(close_handler) {
    this._close_handler = call_or_eval.bind(null, null, close_handler);
    return this;
  },

  /**
   * Take an un-sent async request (on which you've done things like setURI,
   * setData, setReadOnly, or setMethod as applicable), and send it.  The
   * resulting payload should be constructed through the DialogRespose class:
   *
   *   $response = new DialogResponse();
   *   $response->setTitle('Dialog title HTML')
   *            ->setBody('Dialog body HTML',
   *            ->setButtons(array(
   *                DialogResponse::OK,
   *                DialogResponse::Button('help', fbt('Help')),
   *                DialogResponse::CANCEL,
   *              )),
   *            ->setModal(true)
   *            ->send()
   *
   * In particular, for any setXXX method in the JS Dialog class (except
   * setAsync itself), there should be a corresponding setXXX method in
   * the PHP DialogResponse class (and, if there isn't, then someone probably
   * just forgot it and you should add it).
   */
  setAsync : function(async_request) {

    var handler = function(response) {
      if (this._async_request != async_request) {
        return;
      }
      this._async_request = null;

      var payload = response.getPayload();
      if (typeof(payload) == 'string') {
        this.setBody(payload);
      } else {
        for (var propertyName in payload) {
          var mutator = this['set' + propertyName.substr(0, 1).toUpperCase()
                                   + propertyName.substr(1)];
          if (!mutator) {
            Util.error("Unknown Dialog property: " + propertyName);
          }
          mutator.call(this, payload[propertyName]);
        }
      }
      this._dirty();
    }.bind(this);

    var hide = bind(this, 'hide');
    async_request
      .setHandler(chain(async_request.getHandler(), handler))
      .setErrorHandler(chain(hide, async_request.getErrorHandler()))
      .setTransportErrorHandler(chain(hide, async_request.getTransportErrorHandler()))
      .send();

    this._async_request = async_request;
    this._dirty();
    return this;
  },

  _dirty : function() {
    if (!this._is_dirty) {
      this._is_dirty = true;
      bind(this, '_update').defer();
    }
  },

  _update : function() {
    this._is_dirty = false;

    if (!this._showing) {
      return;
    }

    // autohide requested, not running an async request, not already autohiding
    if (this._autohide &&
        !this._async_request &&
        !this._autohide_timeout) {
      this._autohide_timeout = setTimeout(bind(this, 'hide'), this._autohide);
    }

    // Handle class, this has to be done before we display the Dialog
    if (this._class_name) {
      this._pd.setClassName(this._class_name);
    }

    if (!this._async_request) {

      // Construct HTML in case where we're not just "Loading...".

      var html = [];

      if (this._title) {
        html.push('<h2><span>' + this._title + '</span></h2>');
      }

      html.push('<div class="dialog_content">');

        if (this._summary) {
          html.push('<div class="dialog_summary">');
            html.push(this._summary);
          html.push('</div>');
        }

        html.push('<div class="dialog_body">');
          html.push(this._body);
        html.push('</div>');

        if (this._buttons || this._buttons_message) {
          html.push('<div class="dialog_buttons">');

          if (this._buttons_message) {
            html.push('<div class="dialog_buttons_msg">');
              html.push(this._buttons_message);
            html.push('</div>');
          }

          if (this._buttons) {
            this._buttons.forEach(function(button) {
              html.push('<input class="inputsubmit ' + (button.className || '') + '"'
                            + ' type="button"'
                            + (button.name ? (' name="' + button.name + '"') : '')
                            + ' value="' + htmlspecialchars(button.label) + '"'
                            + ' onclick="Dialog.getCurrent().handleButton(this.name);" />');
            }, this);
          }

          html.push('</div>');
        }

      html.push('</div>');

      this._pd.show_dialog(html.join(''));

    } else {

      // Handle "Loading..." state.

      var title = this._title || tx('sh:loading');
      this._pd.show_loading_title(title);

    }

    // Handle modality.

    if (this._modal) {
      this._pd.make_modal();
    }

    // Handle content width.

    if (this._content_width) {
      this._pd.popup.childNodes[0].style.width = (this._content_width + 42) + 'px';
    }

    // Extra properties to pass along.

    this._pd.is_stackable  = this._is_stackable;
    this._pd.close_handler = this._close_handler;

  },

  /**
   * Produce the effect of the user having clicked a given button in the dialog.
   *
   * @param button   either the button object itself or
   *                 the 'name' field of the button object.
   */
  handleButton : function(button) {
    if (typeof(button) == 'string') {
      button = Dialog._findButton(this._buttons, button);
    }

    if (!button) {
      Util.error('Huh?  How did this button get here?');
      return;
    }

    if (call_or_eval(button, button.handler) === false) {
      return;
    }

    if (button != Dialog.CANCEL) {
      if (call_or_eval(this, this._handler, {button: button}) === false) {
        return;
      }
    }

    this.hide();

  },

  _submitForm : function(method, uri, button) {
    var data = this._getFormData();
    data[button.name] = button.label;  // simulate how buttons are normally submitted in forms

    var async_request = new AsyncRequest()
      .setURI(uri)
      .setData(data)
      .setMethod(method)
      .setReadOnly(method == 'GET');
    this.setAsync(async_request);
    return false;
  },

  _getFormData : function() {
    var dialog_content_divs = DOM.scry(this._pd.content, 'div.dialog_content');
    if (dialog_content_divs.length != 1) {
      Util.error(dialog_content_divs.length
                 + " dialog_content divs in this dialog?  Weird.");
    }
    return serialize_form(dialog_content_divs[0]);
  }

});

Dialog._findButton = function(buttons, name) {
  for (var i = 0; i < buttons.length; ++i) {
    if (buttons[i].name == name) {
      return buttons[i];
    }
  }
  return null;
};

/**
 * Perform general set up for dialog boxes when the very first dialog is created.
 */
Dialog._setup = function() {
  if (Dialog._is_set_up) {
    return;
  }
  Dialog._is_set_up = true;

  // Escape key handler.
  var filter = function(event, type) {  // don't filter based on event target
    return KeyEventController.filterEventTypes(event, type)
        && KeyEventController.filterEventModifiers(event, type);
  };
  KeyEventController.registerKey('ESCAPE', Dialog._handleEscapeKey, filter);
};

/**
 * If there's a cancel button, simulate the user having pressed it.  Or, if
 * there's only one button, simluate the user having pressed that.
 */
Dialog._handleEscapeKey = function(event, type) {
  var dialog = Dialog.getCurrent();
  if (!dialog) {
    return true;
  }

  var buttons = dialog._buttons;
  if (!buttons) {
    return true;
  }

  var cancel_button = Dialog._findButton(buttons, 'cancel');
  if (cancel_button) {
    var button_to_simulate = cancel_button;
  } else if (buttons.length == 1) {
    var button_to_simulate = buttons[0];
  } else {
    return true;
  }

  dialog.handleButton(button_to_simulate);
  return false;
}




 