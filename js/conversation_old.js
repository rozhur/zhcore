var typingBreak = null;
addWebSocketEventListener('message', function(e) {
    var data = JSON.parse(e.data);
    var block = $('.block-conversation');
    var cont = block.find('.messages-container');
    var isDown = window.innerHeight + window.scrollY >= document.body.offsetHeight - 15;
    switch (data['type']) {
        case 'read': {
            var unread = cont.find('.message-item.is-unread');
            var badge = $('.badge.badge--conversation:not([data-badge="0"])');
            var newBadge = parseInt(badge.attr('data-badge')) - unread.length;
            badge.attr('data-badge', newBadge < 0 ? 0 : newBadge);
            unread.removeClass('is-unread');
            break;
        }
        case 'typing': {
            var _cont = $('.block-conversation.block-conversation--' + data['conv_id'] + ' .messages-container');
            if (_cont.find('.typing-message').length === 0) {
                _cont.append('<div class="typing-message">' + data['username'] + ' набирает сообщение</div>');
                if (isDown) {
                    scrollDown();
                }
            }
            clearTimeout(typingBreak);
            typingBreak = setTimeout(function() {
                _cont.find('.typing-message').remove();
            }, 3000);
            break;
        }
        case 'message': {
            var sender = data['user'];
            var receiver = data['receiver'];
            if (block.data('receiver') === sender || block.data('receiver') === receiver) {
                if (cont.length > 0) {
                    cont.find('.empty-message').remove();
                    cont.find('.typing-message').remove();
                    cont.append(data['chat_message']);
                    var m_receiver = $('.message-receiver').data('user-id');
                    if (m_receiver === sender) {
                        if (!document.hidden) read();
                    }
                }
                if (isDown) {
                    scrollDown();
                }
            }
            var dialogs = $('.block-dialogs');
            var dialog = dialogs.find('.conv-item[data-conv="' + data['conv_id'] + '"]');
            if (dialog.data('receiver') === receiver) {
                dialog.html(data['dialog_update_sender']);
            } else {
                dialog.html(data['dialog_update_receiver']);
            }
            break;
        }
    }
});
$(document).ready(function() {
    read();
});
var afterClick = false;
$(window).on('resize', function() {
    if (afterClick) {
        scrollDown();
        afterClick = false;
    }
})
$(window).on('focus blur', function() {
    read();
});
var typingTimeout = null;
$('body').on('input', 'input[name="message"]', function(e) {
    var editor = $('.message-editor');
    var input = $(e.target);
    if (input.val().trim().length === 0) {
        editor.addClass('is-empty');
    } else {
        editor.removeClass('is-empty');
    }
    if (typingTimeout === null) {
        $.ajax({
            url: location.href + '/typing',
            type: 'post',
            success: function() {
                typingTimeout = setTimeout(function() {
                    typingTimeout = null;
                }, 3000);
            }
        });
    }
}).on('submit', '.message-editor', function(e) {
    e.preventDefault();
    var target = $(e.target);
    var path = target.attr('action');
    var input = target.find('input[name="message"]');
    var message = input.val();
    if (message.trim().length === 0) {
        input.addTemporaryClass('input-error', 250);
        return;
    }
    input.val('');
    target.addClass('is-empty');
    $.ajax({
        url: path,
        type: 'post',
        data: {message: message},
        success: function() {
            scrollDown();
        }
    });
}).on('click', '.message-editor input[name="message"]', function() {
    var isDown = window.innerHeight + window.scrollY >= document.body.offsetHeight - 30;
    if (isDown) {
        afterClick = true;
    }
});
function read() {
    var block = $('.block-conversation');
    var cont = block.find('.messages-container');
    var unread = cont.find('.message-item.is-unread');
    if (unread.length === 0) {
        return;
    }    var author = unread.data('user-id');
    if (author !== user_id) {
        $.ajax({
            type: 'post',
            url: location.href + '/read'
        })
    }
}
function load(href) {
    $.ajax({
        url: href,
        type: 'post',
        data: {ajax: true},
        success: function(s) {
            $('.body-content').html(s);
            scrollDown();
            read();
        }
    })
}
function scrollDown() {
    $('html, body').animate({
        scrollTop: $(document).height() - $(window).height()
    }, 1);
}
$('.body-content').on('click', 'a', function(e) {
    var link = $(e.target);
    if (link.hasClass('no-ajax') || link.parent().parent().hasClass('messages-container')) {
        return;
    }
    e.preventDefault();
    var href = link.attr('href');
    if (href) {
        if (href.endsWith('conv')) {
            $('.block-conversation').addClass('is-hide');
            $('.block-dialogs').removeClass('is-selected');
        } else if (link.hasClass('conv-item')) {
            $('.block-dialogs').find('.conv-item.is-selected').removeClass('is-selected');
            link.addClass('is-selected');
            $('.block-conversation').addClass('is-loading');
        }
        load(href);
        window.history.pushState({state: 'new'}, '', href);
    }
});
window.onpopstate = function() {
    load(location.href);
}