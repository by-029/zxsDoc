/**
 * Preformatted Text Dialog Plugin for Editor.md
 */
(function() {
    var factory = function (exports) {
        "use strict";
        
        var pluginName = "preformatted-text-dialog";
        
        exports.fn.preformattedTextDialog = function() {
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








