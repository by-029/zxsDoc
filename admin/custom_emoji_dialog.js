/**
 * 自定义 Emoji 对话框
 * 替换 Editor.md 的默认 emoji 功能，使用本地表情图片
 */

(function() {
    // 加载自定义 emoji 数据
    var customEmojiData = null;
    
    // 初始化：加载 emoji 数据
    function loadEmojiData() {
        if (customEmojiData) return Promise.resolve(customEmojiData);
        
        return $.ajax({
            url: 'get_custom_emojis.php',
            dataType: 'json'
        }).then(function(data) {
            customEmojiData = data;
            return data;
        });
    }
    
    function createEmojiDialog(editor) {
        var dialog = $('<div class="custom-emoji-dialog" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; border:2px solid #ddd; border-radius:8px; padding:20px; z-index:10000; max-width:600px; max-height:500px; box-shadow:0 4px 20px rgba(0,0,0,0.3);">' +
            '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">' +
            '<h3 style="margin:0; font-size:18px;">Emoji 表情</h3>' +
            '<button class="emoji-close-btn" style="background:none; border:none; font-size:24px; cursor:pointer; color:#999;">&times;</button>' +
            '</div>' +
            '<div class="emoji-tabs" style="display:flex; gap:10px; margin-bottom:15px; border-bottom:2px solid #eee;">' +
            '</div>' +
            '<div class="emoji-content" style="max-height:350px; overflow-y:auto;">' +
            '</div>' +
            '<div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px; border-top:1px solid #eee; padding-top:10px;">' +
            '<button class="emoji-cancel-btn" style="padding:8px 20px; border:1px solid #ddd; background:white; border-radius:4px; cursor:pointer;">取消</button>' +
            '<button class="emoji-confirm-btn" style="padding:8px 20px; border:none; background:#3498db; color:white; border-radius:4px; cursor:pointer;">确定</button>' +
            '</div>' +
            '</div>');
        
        $('body').append(dialog);
        
        var selectedEmoji = null;
        var currentTab = null;
        
        function closeDialog() {
            dialog.hide();
            $('.emoji-overlay').remove();
        }
        
        function createOverlay() {
            if ($('.emoji-overlay').length === 0) {
                $('body').append('<div class="emoji-overlay" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999;"></div>');
                $('.emoji-overlay').on('click', closeDialog);
            }
        }
        
        function showDialog() {
            createOverlay();
            loadEmojiData().then(function(data) {
                if (data && data.length > 0) {
                    var tabsHtml = '';
                    data.forEach(function(folder, index) {
                        var active = index === 0 ? 'active' : '';
                        tabsHtml += '<button class="emoji-tab-btn ' + active + '" data-index="' + index + '" style="padding:8px 15px; border:none; background:none; border-bottom:2px solid transparent; cursor:pointer; font-size:14px;">' + folder.name + '</button>';
                    });
                    dialog.find('.emoji-tabs').html(tabsHtml);
                    
                    if (data.length > 0) {
                        showTab(0, data);
                    }
                    
                    dialog.find('.emoji-tab-btn').on('click', function() {
                        var index = $(this).data('index');
                        dialog.find('.emoji-tab-btn').removeClass('active');
                        $(this).addClass('active');
                        showTab(index, data);
                    });
                } else {
                    dialog.find('.emoji-content').html('<p style="text-align:center; padding:40px; color:#999;">暂无表情</p>');
                }
            });
            dialog.show();
        }
        
        function showTab(index, data) {
            if (index < 0 || index >= data.length) return;
            
            currentTab = index;
            var folder = data[index];
            var contentHtml = '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(60px, 1fr)); gap:10px; padding:10px;">';
            
            folder.images.forEach(function(img) {
                contentHtml += '<div class="emoji-item" data-path="' + img.path + '" style="text-align:center; padding:5px; border:2px solid transparent; border-radius:4px; cursor:pointer; transition:all 0.2s;">' +
                    '<img src="' + img.path + '" style="max-width:50px; max-height:50px; display:block; margin:0 auto;" onerror="this.style.display=\'none\';">' +
                    '<span style="font-size:11px; color:#666; display:block; margin-top:5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + img.display + '</span>' +
                    '</div>';
            });
            
            contentHtml += '</div>';
            dialog.find('.emoji-content').html(contentHtml);
            
            dialog.find('.emoji-item').on('click', function() {
                dialog.find('.emoji-item').css({'border-color': 'transparent', 'background': 'transparent'});
                $(this).css({'border-color': '#3498db', 'background': '#ecf0f1'});
                selectedEmoji = $(this).data('path');
            });
            
            dialog.find('.emoji-item').on('mouseenter', function() {
                if ($(this).css('border-color') !== 'rgb(52, 152, 219)') {
                    $(this).css('background', '#f8f9fa');
                }
            }).on('mouseleave', function() {
                if ($(this).css('border-color') !== 'rgb(52, 152, 219)') {
                    $(this).css('background', 'transparent');
                }
            });
        }
        
        dialog.find('.emoji-close-btn, .emoji-cancel-btn').on('click', closeDialog);
        dialog.find('.emoji-confirm-btn').on('click', function() {
            if (selectedEmoji) {
                var cm = editor.cm;
                var selection = cm.getSelection();
                var imageMarkdown = '![' + selectedEmoji.split('/').pop().replace(/\.[^/.]+$/, '') + '](' + selectedEmoji + ')';
                cm.replaceSelection(imageMarkdown);
            }
            closeDialog();
        });
        
        $(document).on('click', '.emoji-tab-btn', function() {
            $('.emoji-tab-btn').css({
                'border-bottom-color': 'transparent',
                'color': '#666'
            });
            $(this).css({
                'border-bottom-color': '#3498db',
                'color': '#3498db',
                'font-weight': 'bold'
            });
        });
        
        if (customEmojiData && customEmojiData.length > 0) {
            $('.emoji-tab-btn').first().css({
                'border-bottom-color': '#3498db',
                'color': '#3498db',
                'font-weight': 'bold'
            });
        }
        
        return {
            show: showDialog,
            close: closeDialog
        };
    }
    
    window.initCustomEmojiDialog = function(editor) {
        var emojiDialog = createEmojiDialog(editor);
        return emojiDialog;
    };
})();

