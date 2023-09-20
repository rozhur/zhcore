!function($, window, document) {
    var conversations = {};
    var selectedConversation = 0;

    var blockDialogs = $('.block-dialogs');
    var blockConv = $('.block-conversation');
    var blockHeader = blockConv.find('.block-header');
    var msgCont = blockConv.find('.block-messages .messages-container');
    var submit = blockConv.find('.message-editor');

    var coreMessage = function(params) {
        params = $.extend({
            Conversation: null,
            conversation_id: 0,
            date: "",
            is_read: false,
            message: "",
            message_id: 0,
            sender_User: core.visitor,
            sender_user_id: core.visitor.user_id
        }, params || {});
        return params;
    }

    var buildMessage = function(msg, span, onlyTime) {
        var sender = msg['sender_User'];
        var datetime = msg['date'].split(' ', 2);
        return $('<div class="message-item' + (!msg['is_read'] ? ' is-unread' : '') + (msg['sender_user_id'] === core.visitor.user_id ? ' is-you' : '') + '" data-sender="' + sender['user_id'] + '" data-message-id="' + msg['message_id'] + '"><div class="message"><' + (span === true ? 'span' : 'a') + ' class="message-user"' + (span === true ? '' : ' href="' + core.root + '/id' + sender['user_id'] + '"') + '>' + sender['username'] + '</' + (span === true ? 'span' : 'a') + '> <span class="message-text">' + msg['message'] + '</span><span class="message-date">' + (onlyTime === true && datetime[1] !== undefined ? datetime[1] : msg['date']) + '</span></div></div>');
    }

    function selectConversation(id, trying) {
        var input = submit.find('.input[name="message"]');
        input.html('');
        submit.addClass('is-empty');
        if (selectedConversation > 0) {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 5) {
                conversations[selectedConversation]['scroll'] = -1;
            } else {
                conversations[selectedConversation]['scroll'] = window.scrollY;
            }
        }
        selectedConversation = -1;
        if (!id || id === 0) {
            blockDialogs.removeClass('is-selected').find('li a').removeClass('is-selected');
            blockConv.removeClass('is-active').removeClass('is-loading');
            return;
        }
        id = parseInt(id);
        blockDialogs.find('li a.is-selected').removeClass('is-selected');
        blockDialogs.addClass('is-selected').find('li a[data-conv="' + id + '"]').addClass('is-selected');

        if (trying && conversations.hasOwnProperty(id) === false) {
            selectedConversation = 0;
            blockConv.addClass('is-loading');
            loadConversation(id, function() {
                selectConversation(id, false)
            });
            return;
        }

        var conv = conversations[id];
        if (conv === undefined) {
            return;
        }

        msgCont.html('');

        selectedConversation = id;

        var receiver = conv['receiver'];

        blockConv.attr('data-receiver', receiver['user_id']);
        blockHeader.find('.message-receiver').attr('href', core.root + '/id' + receiver['user_id']).html(receiver['username']);
        var messages = conv['messages'];
        if (messages.length === 0) {
            msgCont.append('<div class="empty-message">У вас еще не было диалога с ' + receiver['username'] + '</div>');
        } else {
            for (var i in conv['messages']) {
                var msg = conv['messages'][i];
                var datetime = msg['date'].split(' ', 2);
                if (msgCont.find('.message-date-separator[data-date="' + datetime[0] + '"]').length === 0) {
                    msgCont.append('<div class="message-date-separator" data-date="' + datetime[0] + '"></div>');
                }
                msgCont.append(buildMessage(msg, false, true));
            }
        }
        blockConv.addClass('is-active').removeClass('is-loading');
        submit.attr('action', core.root + '/conv' + id + '/send');
        input.html(conversations[selectedConversation]['last_message']);

        input.trigger('input');
        scrollDown(conversations[selectedConversation]['scroll']);
        read();
    }

    function loadConversation(id, fn) {
        if (!id || id === 0 || id === undefined) {
            return;
        }
        $.ajax({
            type: 'post',
            url: core.root + '/conv' + id,
            data: {ajax: true},
            success: function(s) {
                var data = JSON.parse(s);
                var conv = data['conv'];
                conv['receiver'] = data['receiver'];
                conv['messages'] = data['messages'];
                conv['last_message'] = data['last_message'];
                conv['page'] = 0;
                conv['scroll'] = -1;
                conversations[conv['conversation_id']] = conv;
                if (fn && selectedConversation > -1) {
                    fn();
                }
            }
        });
    }

    function read() {
        var messages = [];
        msgCont.find('.message-item.is-unread:not([data-sender="' + core.visitor.user_id + '"]').each(function() {
            var msg = $(this);
            if (msg.isVisible(86)) {
                messages.push(msg.data('message-id'));
            }
        });
        if (messages.length > 0) {
            $.ajax({
                url: core.root + '/conv' + selectedConversation + '/read',
                type: 'post',
                data: {m: messages.join(',')}
            })
        }
    }

    function isDown() {
        return window.innerHeight + window.scrollY >= document.body.offsetHeight - 15;
    }

    function scrollDown(scroll) {
        if (scroll !== undefined && scroll !== -1) {
            $('html, body').animate({
                scrollTop: scroll
            }, 1);
        } else {
            var firstUnread = msgCont.find('.message-item.is-unread:not([data-sender="' + core.visitor.user_id + '"]):first');
            var offsetTop = firstUnread.length > 0 ? firstUnread.offset().top + firstUnread.addTemporaryClass('is-highlight', 1000).height() + 71 : $(document).height();
            $('html, body').animate({
                scrollTop: (offsetTop - $(window).height())
            }, 1);
        }
    }

    var typingTimeout = null;

    function type(message) {
        if (typingTimeout === null) {
            $.ajax({
                url: core.root + '/conv' + selectedConversation + '/typing',
                type: 'post',
                data: {state: 'start', message: message}
            });
        }
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(function() {
            $.ajax({
                url: core.root + '/conv' + selectedConversation + '/typing',
                type: 'post',
                data: {state: 'stop', message: message}
            });
            typingTimeout = null;
        }, 1000);
    }
    var stopMsgLoading = false;
    function loadMessages() {
        if (selectedConversation === 0 || conversations[selectedConversation]['messages'].length < 20 || stopMsgLoading) {
            return;
        }
        var selConv = selectedConversation;
        var loaded = conversations[selectedConversation]['messages'].length;
        $.ajax({
            url: core.root + '/conv' + selectedConversation,
            type: 'post',
            data: {ajax: true, loaded: loaded},
            success: function(s) {
                var data = JSON.parse(s);
                if (data['messages'].length === 0) {
                    stopMsgLoading = true;
                    return;
                }
                conversations[selectedConversation]['messages'] = data['messages'].concat(conversations[selectedConversation]['messages']);
                conversations[selectedConversation]['loaded'] += data['messages'].length;
                if (selConv !== selectedConversation) {
                    return;
                }
                var height = 100;
                var scroll = window.scrollY;
                for (var i = data['messages'].length; i > 0; i--) {
                    var msg = data['messages'][i - 1];
                    var built = buildMessage(msg, false, true);
                    var datetime = msg['date'].split(' ', 2);
                    msgCont.prepend(built);
                    height += built.outerHeight();
                    var dateSeparator = msgCont.find('.message-date-separator[data-date="' + datetime[0] + '"]');
                    if (dateSeparator.length === 0) {
                        dateSeparator = msgCont.prepend('<div class="message-date-separator" data-date="' + datetime[0] + '"></div>').find('.message-date-separator[data-date="' + datetime[0] + '"]');
                        height += dateSeparator.outerHeight();
                    }
                }
                $('html, body').animate({scrollTop: scroll + height}, 1);
            }
        });
    }

    core.addWebSocketEventListener('message', function(e) {
        var data = JSON.parse(e.data);
        var type = data['type'];

        console.log(data);

        var is_down = isDown();

        var conv_id = data['conv_id'];

        var dialog = blockDialogs.find('.conv-item[data-conv="' + conv_id + '"]');
        var unread = parseInt(dialog.attr('data-unread'));

        switch (type) {
            case 'new_message': {
                var message = data['message'];
                var builtMessage = buildMessage(message, false, true);
                if (selectedConversation === conv_id) {
                    msgCont.find('.empty-message').remove();
                    msgCont.find('.typing-message').remove();
                    var preloadedMsg = msgCont.find('.message-item[data-message-id="0"]:first');
                    var datetime = message['date'].split(" ", 2);
                    if (core.visitor.user_id === message['sender_user_id'] && preloadedMsg.length > 0) {
                        preloadedMsg
                            .html(builtMessage.html())
                            .attr('data-message-id', message['message_id'])
                            .removeClass('is-loading');
                    } else {
                        msgCont.append(builtMessage);
                    }

                    if (msgCont.find('.message-date-separator[data-date="' + datetime[0] + '"]').length === 0) {
                        msgCont.find('.message-item[data-message-id="' + message['message_id'] + '"]').before('<div class="message-date-separator" data-date="' + datetime[0] + '"></div>');
                    }
                    typingTimeout = null;
                    if (is_down) {
                        scrollDown();
                    }
                }
                if (!document.hidden) {
                    read();
                }
                if (conversations.hasOwnProperty(conv_id)) {
                    conversations[conv_id]['messages'].push(message);
                    conversations[conv_id]['last_message_id'] = message['message_id'];
                }
                var sender = message['sender_user_id'];
                if (sender !== core.visitor.user_id) {
                    dialog.attr('data-unread', unread + 1);
                }
                var receiver = core.visitor.user_id === message['Conversation']['first_user_id'] ? message['Conversation']['second_User'] : message['Conversation']['first_User'];
                if (dialog.length === 0) {
                    blockDialogs.find('li span.conv-item').parent().remove();
                    dialog = blockDialogs.prepend('<li><a data-unread="' + (core.visitor.user_id === message['sender_user_id'] ? '0' : '1') + '" data-receiver="' + receiver['user_id'] + '" data-conv="' + conv_id + '" class="conv-item" href="' + core.root + '/conv' + conv_id + '"><span class="message-user">' + receiver['username'] + '</span> <span class="message-text"></span><span class="message-date"></span></a>').find('.conv-item[data-conv="' + conv_id + '"]');
                } else {
                    dialog.parent('li').remove();
                    dialog = blockDialogs.prepend('<li>' + dialog.parent().html() + '</li>').find('.conv-item[data-conv="' + conv_id + '"]');
                }
                if (selectedConversation === conv_id) {
                    blockDialogs.find('.conv-item.is-selected').removeClass('is-selected');
                    dialog.addClass('is-selected');
                }
                dialog.find('.message-text').html((core.visitor.user_id === message['sender_user_id'] ? '<i>(Вы)</i> ' : '') + message['message'].replace(/<br\s?\/?>/g, ' '));
                dialog.find('.message-date').html(message['date']);
                dialog.find('.message-typing').remove();
                break;
            }
            case 'read': {
                var messages = data['messages'];
                for (var i in messages) {
                    var id = messages[i];
                    if (conv_id === selectedConversation) {
                        msgCont.find('.message-item.is-unread[data-message-id="' + id + '"]').removeClass('is-unread');
                    }
                    conversations[selectedConversation]['messages'][i]['is_read'] = true;
                }
                dialog.attr('data-unread', Math.max(unread - messages.length, 0));
                break;
            }
            case 'typing_message': {
                if (data['state'] === 'start') {
                    if (conv_id === selectedConversation) {
                        if (msgCont.find('.typing-message').length === 0) {
                            msgCont.append('<div class="typing-message">' + data['typer']['username'] + ' набирает сообщение</div>');
                            if (is_down) {
                                scrollDown();
                            }
                        }
                    } else {
                        dialog.find('.message-user').after('<span class="message-typing">&nbsp;печатает</span>');
                    }
                } else {
                    msgCont.find('.typing-message').remove();
                    dialog.find('.message-typing').remove();
                }
            }
        }
    })

    var needScroll = false;
    $(window).on('resize', function() {
        if (needScroll) {
            scrollDown();
            needScroll = false;
        }
    });

    var readTimeout = null;
    $(document).on('scroll', function() {
        if (selectedConversation > 0 && window.scrollY === 0) {
            loadMessages();
        }
        clearTimeout(readTimeout);
        readTimeout = setTimeout(function() {
            read();
            readTimeout = null;
        }, 1000);
    }).on('click focus blur', read);

    $(window).on('focus blur', read);

    $('body').on('click', '.block.block-inline a, .alert .alert-content', function(e) {
        var link = $(this);
        var href = link.attr('href');
        if (!href.startsWith(core.root + '/conv')) {
            return;
        }
        e.preventDefault();
        var conv_id = link.attr('data-conv');
        if (conv_id === undefined) {
            conv_id = '';
        }
        selectConversation(conv_id, true);
        history.pushState({state: 'new'}, '', core.root + '/conv' + conv_id);
    });

    $('.message-editor').on('submit', function(e) {
        e.preventDefault();
        var editor = $(this);
        var input = editor.find('.input[name="message"]');
        input.focus();
        if (input.text().trim().length === 0) {
            input.addTemporaryClass('input-error', 250);
            return;
        }
        var message = input.safeText();
        var action = editor.attr('action');
        editor.addClass('is-empty');
        var msg = coreMessage({
            message: message
        });
        var html_msg = buildMessage(msg, false, true);
        html_msg.addClass('is-loading');
        var is_down = isDown();
        msgCont.append(html_msg);
        if (is_down) {
            scrollDown();
        }
        blockConv.find('.empty-message').remove();
        input.html('');
        conversations[selectedConversation]['last_message'] = '';
        $.ajax({
            url: action,
            type: 'post',
            data: {message: message}
        });
    }).on('paste', '.input[name="message"]', function(e) {
        e.preventDefault();
        if (window.clipboardData) {
            var content = window.clipboardData.getData('Text');
            if (window.getSelection) {
                var selObj = window.getSelection();
                var selRange = selObj.getRangeAt(0);
                selRange.deleteContents();
                selRange.insertNode(document.createTextNode(content));
            }
        } else if (e.originalEvent.clipboardData) {
            content = (e.originalEvent || e).clipboardData.getData('text/plain');
            document.execCommand('insertText', false, content);
        }
    }).on('input', '.input[name="message"]', function() {
        var input = $(this);
        var editor = $(this).parent();
        if (input.text().trim().length === 0) {
            editor.addClass('is-empty');
        } else {
            editor.removeClass('is-empty');
        }
        var value = input.safeText();
        var lastMessage = conversations[selectedConversation]['last_message'];
        if (input.text().trim().length > 0 && Math.abs(lastMessage.length - value.length) > 0) {
            type(value);
            conversations[selectedConversation]['last_message'] = value;
        }
        if (needScroll) {
            scrollDown();
            needScroll = false;
        }
    }).on('click', function() {
        if (isDown()) {
            needScroll = true;
        }
    });

    $(document).on('keypress', function(e) {
        var focused = $('.message-editor .input:focus');
        if (focused.length > 0) {
            if (window.innerWidth > 700 && e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                focused.parent().trigger('submit');
            }
            if (isDown()) {
                needScroll = true;
            }
        }
    });

    window.onpopstate = function() {
        var href = location.pathname;
        var conv_id = href.replace(new RegExp('^' + core.root + '/conv', ''), '');
        selectConversation(conv_id, true);
    }

    selectConversation(blockConv.data('conv'), true);
}
(jQuery, window, document);