!function($, window, document) {
    $.fn.extend({
        addTemporaryClass: function(className, duration, fn) {
            var elements = this;
            setTimeout(function() {
                elements.removeClass(className);
                if (fn) {
                    fn();
                }
            }, duration);

            return this.each(function() {
                $(this).addClass(className);
            });
        },
        showDelayed: function(delay, fn) {
            var elements = this;
            if (!elements.hasClass('is-active')) {
                setTimeout(function() {
                    elements.addClass('is-active');
                    if (fn) {
                        fn();
                    }
                }, delay);
            } else {
                elements.removeClass('.is-active');
                if (fn) {
                    fn();
                }
            }

            return this.each(function() {
                $(this).addTemporaryClass('is-transitioning', delay);
            });
        },
        hideAnimated: function(speed, fn) {
            var el = this;

            el.css('height', 'auto');
            el.css('display', 'block');

            var height = el.height();
            var hide = el.hasClass('is-active');

            if (!hide) {
                el.css('height', 0);
            } else {
                el.removeClass('is-active');
            }
            el.addTemporaryClass('is-transitioning', speed);
            el.animate({
                    opacity: !hide,
                    height: hide ? 0 : height,
                }, speed, 'linear',
                function() {
                    el.toggleClass('is-active');
                    el.attr('style', null);
                    if (fn) {
                        fn();
                    }
                }
            );
        },
        isVisible: function(paddingTop, paddingBottom) {
            var $elem = $(this);
            var $window = $(window);

            var docViewTop = $window.scrollTop();
            var docViewBottom = docViewTop + $window.height();

            if (paddingTop) {
                docViewTop += paddingTop;
            }

            if (paddingBottom) {
                docViewBottom -= paddingBottom;
            }

            var elemTop = $elem.offset().top;
            var elemBottom = elemTop + $elem.height();

            return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
        },
        safeText: function() {
            var element = $(this);
            var value = element.html();
            return value.trim()
                .replace(/&nbsp;/g, ' ')
                .replace(/^(<br\s?\/?>)+|(<br\s?\/?>)+$/, '')
                .replace(/(<br\s?\/?>){3,}/, '<br><br>')
                .replace(/<(div|p)[^>]*>/g, '<br>')
                .replace(/((?!<br\s?\/?>)<[^>]*>?)/gm, '');
        }
    })

    $(document).ready(function() {
        resize();
    });

    function resize() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', vh + 'px');
    }

    window.onresize = function() {
        resize();
    }

    core = $.extend({
        wsEvents: {open: [], message: [], close: []},
        addWebSocketEventListener: function(event, fn) {
            core.wsEvents[event][core.wsEvents[event].length] = fn;
        }
    }, core || {});

    if (core.visitor.user_id > 0) {
        core.addWebSocketEventListener('message', function(e) {
            var data = JSON.parse(e.data);
            switch (data['type']) {
                case 'new_message': {
                    var message = data['message'];
                    if (!location.href.endsWith('conv' + message['conversation_id'])) {
                        var alertContainer = $('.alert-container');
                        if (alertContainer.length === 0) {
                            alertContainer = $('body').append('<div class="alert-container"></div>').find('.alert-container');
                        }
                        alertContainer.append('<div class="alert"><h4 class="alert-header">Новое сообщение!<span class="alert-close"></span></h4><a href="' + core.root + '/conv' + message['conversation_id'] + '" class="alert-content" data-conv="' + message['conversation_id'] + '"><span class="alert-username">' + message['sender_User']['username'] + '</span><span class="alert-message">' + message['message'] + '</span></a></div>');
                        var badge = $('.badge.badge--conversation');
                        badge.attr('data-badge', parseInt(badge.attr('data-badge')) + 1);
                        var lastAlert = alertContainer.find('.alert').last();
                        lastAlert.hideAnimated(50);
                        lastAlert.on('click', '.alert-close, .alert-content', function() {
                            lastAlert.hideAnimated(50, function() {
                                lastAlert.remove()
                            });
                        })
                        setTimeout(function() {
                            lastAlert.hideAnimated(50, function() {
                                lastAlert.remove()
                            });
                        }, 10000);
                    }
                }
            }
        });
    }

    $('body').on('click', '.menu-trigger', function(e) {
        var trigger = $(this);
        var menu = trigger.next('.menu');
        if (menu.length === 0) {
            return;
        }
        menu.toggleClass('is-open');
    });
}
(jQuery, window, document);

