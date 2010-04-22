var Lightbox = {
    window: jQuery(window),
    container: null,
    options: null,

    create: function() {
        var me = this;

        this.container = jQuery('<div class="lightbox lightbox-loading"><div class="lightbox-close">Close window</div><div class="lightbox-content"></div></div>').css( {
            top: 0,
            left: 0,
            width: 0,
            height: 0,
            position: 'absolute',
            'z-index': 9991,
            display: 'none'
        });

        jQuery('#w3tc').append(this.container);

        this.window.resize(function() {
            me.resize();
        });

        this.window.scroll(function() {
            me.resize();
        });

        this.container.find('.lightbox-close').click(function() {
            me.close();
        });
    },

    open: function(options) {
        var me = this;

        this.options = jQuery.extend( {
            width: 400,
            height: 300,
            offsetTop: 100,
            content: null,
            url: null,
            callback: null
        }, options);

        if (!this.container) {
            this.create();
        }

        this.container.css( {
            width: this.options.width,
            height: this.options.height
        });

        if (this.options.content) {
            this.content(options.content);
        } else if (this.options.url) {
            this.load(this.options.url, this.options.callback);
        }

        Overlay.show(this);

        this.resize();
        this.container.show();
        currentLightbox = this;
    },

    close: function() {
        this.container.hide();
        Overlay.hide();
        currentLightbox = null;
    },

    resize: function() {
        this.container.css( {
            top: this.window.scrollTop() + this.options.offsetTop,
            left: this.window.scrollLeft() + this.window.width() / 2 - this.container.width() / 2
        });
    },

    load: function(url, callback) {
        this.content('');
        this.loading(true);
        var me = this;
        jQuery.get(url, {}, function(content) {
            me.loading(false);
            me.content(content);
            if (callback) {
                callback.call(this, me);
            }
        });
    },

    content: function(content) {
        return this.container.find('.lightbox-content').html(content);
    },

    width: function(width) {
        if (width === undefined) {
            return this.container.width();
        } else {
            return this.container.css( {
                width: width,
                left: this.window.scrollLeft() + this.window.width() / 2 - width / 2
            });
        }
    },

    height: function(height) {
        if (height === undefined) {
            return this.container.height();
        } else {
            return this.container.css( {
                height: height,
                top: this.window.scrollTop() + this.options.offsetTop
            });
        }
    },

    loading: function(loading) {
        return (loading === undefined ? this.container.hasClass('lightbox-loader') : (loading ? this.container.addClass('lightbox-loader') : this.container.removeClass('lightbox-loader')));
    }
};

var Overlay = {
    window: jQuery(window),
    container: null,

    create: function() {
        var me = this;

        this.container = jQuery('<div id="overlay" />').css( {
            top: 0,
            left: 0,
            width: 0,
            height: 0,
            position: 'absolute',
            'z-index': 9990,
            display: 'none',
            opacity: 0.6
        });

        jQuery('#w3tc').append(this.container);

        this.window.resize(function() {
            me.resize();
        });

        this.window.scroll(function() {
            me.resize();
        });
    },

    show: function() {
        if (!this.container) {
            this.create();
        }

        this.resize();
        this.container.show();
    },

    hide: function() {
        this.container.hide();
    },

    resize: function() {
        this.container.css( {
            top: this.window.scrollTop(),
            left: this.window.scrollLeft(),
            width: this.window.width(),
            height: this.window.height()
        });
    }
};

function w3tc_lightbox_support_us() {
    Lightbox.open( {
        width: 590,
        height: 200,
        url: 'options-general.php?page=w3-total-cache/w3-total-cache.php&w3tc_action=support_us',
        callback: function(lightbox) {
            jQuery('.button-tweet', lightbox.container).click(function(event) {
                lightbox.close();
                w3tc_lightbox_tweet();
                return false;
            });

            jQuery('.button-rating', lightbox.container).click(function() {
                window.open('http://wordpress.org/extend/plugins/w3-total-cache/', '_blank');
            });

            jQuery('form').submit(function() {
                if (jQuery('select :selected', this).val() == '') {
                    alert('Please select link location!');
                    return false;
                }
            });
        }
    });

}

function w3tc_lightbox_tweet() {
    Lightbox.open( {
        width: 550,
        height: 340,
        url: 'options-general.php?page=w3-total-cache/w3-total-cache.php&w3tc_action=tweet',
        callback: function(lightbox) {
            jQuery('form', lightbox.container).submit(function() {
                var me = this, username = jQuery('#tweet_username').val(), password = jQuery('#tweet_password').val();

                if (username == '') {
                    alert('Please enter your twitter.com username.');
                    return false;
                }

                if (password == '') {
                    alert('Please enter your twitter.com password.');
                    return false;
                }

                jQuery('input', this).attr('disabled', 'disabled');

                jQuery.post('options-general.php?page=w3-total-cache/w3-total-cache.php', {
                    w3tc_action: 'twitter_status_update',
                    username: username,
                    password: password
                }, function(data) {
                    jQuery('input', me).attr('disabled', '');

                    if (data.result) {
                        lightbox.close();
                        alert('Nice! Thanks for telling your friends about us!');
                    } else {
                        alert('Uh oh, seems that that #failed. Please try again.');
                    }
                }, 'json');

                return false;
            });
        }
    });
}

jQuery(function($) {
    $('.button-tweet').click(function() {
        w3tc_lightbox_tweet();
        return false;
    });
});
