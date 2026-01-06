/**
 * Goto Line Dialog Plugin for Editor.md
 */
(function() {
    var factory = function (exports) {
        "use strict";
        
        var pluginName = "goto-line-dialog";
        
        exports.fn.gotoLineDialog = function() {
            // Placeholder plugin
            return false;
        };
        
    };
    
    if (typeof module === "object" && typeof module.exports === "object") {
        module.exports = factory;
    } else if (typeof define === "function" && define.amd) {
        define(factory);
    } else {
        factory(typeof editormd !== "undefined" ? editormd : {});
    }
})();








