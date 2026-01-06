/**
 * Table Dialog Plugin for Editor.md
 * This is a placeholder - functionality is handled in toolbarHandlers
 */
(function() {
    var factory = function (exports) {
        "use strict";
        
        var pluginName = "table-dialog";
        
        exports.fn.tableDialog = function() {
            // Functionality is handled in toolbarHandlers
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
