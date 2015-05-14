document_title = document.title;
current_notif_count = 0;
current_msg_count = 0;
current_followreq_count = 0;

$(function () {
    setInterval(function () {
        SK_intervalUpdates();
    }, 10000);
    
    if ($('.chat-wrapper').length == 1) {
        $('.chat-messages').scrollTop($(this).prop('scrollHeight'));
    }
    
    $(document).on('focusin', '*[data-placeholder]', function() {
        elem = $(this);
        
        if (elem.val() == elem.attr('data-placeholder')) {
            elem.val('');
        }
    });
    
    $(document).on('focusout', '*[data-placeholder]', function() {
        elem = $(this);
        
        if (elem.val().length == 0) {
            elem.val(elem.attr('data-placeholder'));
        }
    });
    
    $(document).on('keyup', '*[data-copy-to]', function() {
        elem = $(this);
        elem_val = elem.val();
        elem_placeholder = elem.attr('data-placeholder');
        
        if (elem_val == elem_placeholder) {
            $(elem.attr('data-copy-to')).val('');
        }
        else {
            $(elem.attr('data-copy-to')).val(elem_val);
        }
    });
    
    $(document).on('keyup', '.auto-grow-input', function() {
        elem = $(this);
        initialHeight = '10px';
        
        if (elem.attr('data-height')) {
            initialHeight = elem.attr('data-height') + 'px';
        }
        
        this.style.height = initialHeight;
        this.style.height = (this.scrollHeight) + 'px';
    });
});

// Interval Updates
function SK_intervalUpdates() {
    
    $.get(SK_source(), {t: 'interval'}, function (data) {
        
        // Get new notifications
        if (typeof(data.notifications) != "undefined" && data.notifications > 0) {
            $('.notification-nav').find('.new-update-alert').text(data.notifications).show();

            if (data.notifications != current_notif_count) {
                document.getElementById('notification-sound').play();
                current_notif_count = data.notifications;
            }
        }
        else {
            $('.notification-nav').find('.new-update-alert').hide();
            current_notif_count = 0;
        }
        
        // Get new messages
        if (typeof(data.messages) != "undefined" && data.messages > 0) {
            $('.message-nav').find('.new-update-alert').text(data.messages).show();
            
            if ($('.online-header').length == 1) {
                SK_getOnlineList('');
                $('.online-header').find('.update-alert').show();
            }
            
            if ($('.chat-wrapper').length == 1) {
                loadNewChatMessages();
            }

            if (data.messages != current_msg_count) {
                document.getElementById('notification-sound').play();
                current_msg_count = data.messages;
            }
        } else {
            $('.message-nav').find('.new-update-alert').hide();
            
            if ($('.online-header').length == 1) {
                $('.online-header').find('.update-alert').hide();
            }

            current_msg_count = 0;
        }
        
        // Get new follow requests
        if (typeof(data.follow_requests) != "undefined" && data.follow_requests > 0) {
            $('.followers-nav')
                .attr('href', $('.followers-nav').attr('href').replace('following', 'requests'))
                .find('.new-update-alert').text(data.follow_requests).show();

            if (data.follow_requests != current_followreq_count) {
                document.getElementById('notification-sound').play();
                current_followreq_count = data.follow_requests;
            }
        } else {
            $('.followers-nav')
                .find('.new-update-alert').hide();
            current_followreq_count = 0;
        }
    });
}

// Follow
function SK_registerFollow(id) {
    element = $('.follow-' + id);
    
    SK_progressIconLoader(element);
    
    $.post(SK_source() + '?t=follow&a=follow', {following_id: id}, function (data) {
        
        if (data.status == 200) {
            element.after(data.html);
            element.remove();
        }
    });
}

/* Lightbox */
function SK_openLightbox(post_id) {
    if ($(".header-wrapper").width() < 960) {
        window.location = 'index.php?tab1=story&id=' + post_id;
    } else {
        $(".sc-lightbox-container").remove();
        $(document.body).append('<div class="pre_load_wrap"><div class="bubblingG"><span id="bubblingG_1"></span><span id="bubblingG_2"></span><span id="bubblingG_3"></span></div></div>');

        $.get(SK_source(), {t: 'post', a: 'lightbox', post_id: post_id}, function (data) {

            if (data.status == 200) {
                $(document.body).append(data.html);
            } else {
                $('.pre_load_wrap').remove();
            }
        });
    }
}

// Open chat
function SK_getChat(recipient_id, recipient_name) {
    chat_container = $('.chat-container');
    
    if (chat_container.length == 1) {
    
        if ($('.header-wrapper').width() < 960) {
            startPageLoadingBar();
            SK_loadPage('?tab1=messages&recipient_id=' + recipient_id);
        } else {
            $(document.body).attr('data-chat-recipient', recipient_id);
            $('.chat-recipient-name').text(recipient_name);
            $('.chat-wrapper').show();
            
            $.get(SK_source(), {t: 'chat', a: 'load_messages', recipient_id: recipient_id} ,function (data) {
                
                if (data.status == 200) {
                    $('.chat-wrapper').remove();
                    $('.chat-container').prepend(data.html);
                    $('.chat-wrapper').show();
                    $('.chat-textarea textarea').keyup();
                    $('#online_' + recipient_id)
                        .find('.update-alert').hide();
                    SK_intervalUpdates();
                }
                
                setTimeout(function() {
                $('.chat-messages').scrollTop($('.chat-messages').prop('scrollHeight'));
                }, 500);
            });
        }
    } else {
        startPageLoadingBar();
        SK_loadPage('?tab1=messages&recipient_id=' + recipient_id);
    }
}

// Close popup window
function SK_closeWindow() {
    $('.window-container').remove();
    $(document.body).css('overflow','auto');
}

// Progress Icon Loader
function SK_progressIconLoader(container_elem) {
    container_elem.each(function() {
        progress_icon_elem = $(this).find('i.progress-icon');
        default_icon = progress_icon_elem.attr('data-icon');
        
        hide_back = false;
        
        if (progress_icon_elem.hasClass('hide') == true) {
            hide_back = true;
        }
        
        if ($(this).find('i.icon-spinner').length == 1) {
            progress_icon_elem
                .removeClass('icon-spinner')
                .removeClass('icon-spin')
                .addClass('icon-' + default_icon);
            if (hide_back == true) {
                progress_icon_elem.hide();
            }
        }
        else {
            progress_icon_elem
                .removeClass('icon-'+default_icon)
                .addClass('icon-spinner icon-spin')
                .show();
        }
        return true;
    });
}

// Generate username
function SK_generateUsername(query) {
    var username = query.replace(/[^A-Za-z0-9_\-\.]/ig, '').toLowerCase();
    $('.register-username-textinput').val(username).keyup();
}

// Check username
function SK_checkUsername(query,timeline_id,target,detailed) {
    target = $(target);
    target_html = '';
    
    $.get(SK_source(), {t: 'username', a: 'check', q: query, timeline_id: timeline_id}, function(data) {
        
        if (data.status == 200) {
            
            if (detailed == true) {
                target_html = '<span style="color: #94ce8c;"><i class="icon-ok"></i> Username available!</span>';
            } else {
                target_html = '<span style="color: #94ce8c;"><i class="icon-ok"></i></span>';
            }
        } else if (data.status == 201) {
            
            if (detailed == true) {
                target_html = '<span style="color: #94ce8c;">This is you!</span>';
            } else {
                target_html = '<span style="color: #94ce8c;"></span>';
            }
        } else if (data.status == 410) {
            
            if (detailed == true) {
                target_html = '<span style="color: #ee2a33;"><i class="icon-remove"></i> Username not available!</span>';
            } else {
                target_html = '<span style="color: #ee2a33;"><i class="icon-remove"></i></span>';
            }
        } else if (data.status == 406) {
            
            if (detailed == true) {
                target_html = '<span style="color: #ee2a33;"><i class="icon-remove"></i> Username should atleast be 4 characters, cannot be only numbers, can contain alphabets [A-Z], numbers [0-9] and underscores (_) only.</span>';
            } else {
                target_html = '<span style="color: #ee2a33;"><i class="icon-remove"></i></span>';
            }
        }
        
        if (target_html.length == 0) {
            target.html('').hide();
        } else {
            target.html(target_html).show();
        }
    });
}

function addEmoToInput(code,input) {
    inputTag = $(input);
    inputVal = inputTag.val();
    
    if (typeof(inputTag.attr('placeholder')) != "undefined") {
        inputPlaceholder = inputTag.attr('placeholder');
        
        if (inputPlaceholder == inputVal) {
            inputTag.val('');
            inputVal = inputTag.val();
        }
        
    }
    
    if (inputVal.length == 0) {
        inputTag.val(code + ' ');
    } else {
        inputTag.val(inputVal + ' ' + code);
    }
    
    inputTag.keyup();
}

function updateCountAlert() {
    $('.update-alert').each(function() {
        update_count = $(this).text();
        update_count = update_count * 1;

        if (update_count == 0) {
            $(this).addClass('hidden');
        }
    });
}