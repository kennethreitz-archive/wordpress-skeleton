function seconds_to_string(seconds) {
    var string = '', days = 0, hours = 0, minutes = 0;
    days = Math.floor(seconds / 86400);
    if (days) {
        seconds -= days * 86400;
        string += days + 'd ';
    }
    hours = Math.floor(seconds / 3600);
    if (hours) {
        seconds -= hours * 3600;
        string += hours + 'h ';
    }
    minutes = Math.floor(seconds / 60);
    if (minutes) {
        seconds -= minutes * 60;
        string += minutes + 'm ';
    }

    if (seconds) {
        string += seconds + 's';
    }

    return string;
}

var Cdn_Export_File = {
    paused: 0,
    limit: 25,
    offset: 0,
    files: [],
    upload_files: [],
    retry_seconds: 10,
    seconds_elapsed: 0,
    timer: null,

    set_progress: function(percent) {
        jQuery('#cdn_export_file_progress .progress-bar').width(percent + '%');
        jQuery('#cdn_export_file_progress .progress-value').html(percent + '%');
    },

    set_status: function(status) {
        jQuery('#cdn_export_file_status').html(status);
    },

    set_processed: function(processed) {
        jQuery('#cdn_export_file_processed').html(processed);
    },

    set_button_text: function(text) {
        jQuery('#cdn_export_file_start').val(text);
    },

    set_last_response: function() {
        var date = new Date();
        jQuery('#cdn_export_file_last_response').html(date.toLocaleTimeString() + ' ' + date.toLocaleDateString());
    },

    set_elapsed: function(text) {
        jQuery('#cdn_export_file_elapsed').html(text);
    },

    add_log: function(path, result, error) {
        jQuery('#cdn_export_file_log').prepend('<div class="log-' + (result == 1 ? 'success' : 'error') + '">' + path + ' <strong>' + error + '</strong></div>');
    },

    clear_log: function() {
        jQuery('#cdn_export_file_log').html('');
    },

    process: function() {
        if (this.paused) {
            return;
        }

        this.upload_files = [];

        for ( var i = this.offset, l = this.files.length, j = 0; i < l && j < this.limit; i++, j++) {
            this.upload_files.push(this.files[i]);
        }

        var me = this;
        if (this.upload_files.length) {
            jQuery.ajax( {
                type: 'POST',
                url: 'options-general.php?page=w3-total-cache/w3-total-cache.php',
                data: {
                    w3tc_action: 'cdn_export_process',
                    'files[]': this.upload_files
                },
                dataType: 'json',
                success: function(data) {
                    me.set_last_response();
                    me.process_callback(data);
                },
                error: function() {
                    me.set_last_response();
                    me.retry(me.retry_seconds);
                }
            });
        }
    },

    retry: function(seconds) {
        if (this.paused) {
            return;
        }
        this.set_status('request failed (retry in ' + seconds + 's)');
        if (seconds) {
            var me = this;
            setTimeout(function() {
                me.retry(--seconds);
            }, 1000);
        } else {
            this.set_status('processing');
            this.process();
        }
    },

    process_callback: function(data) {
        var failed = false;
        for ( var i = 0; i < data.results.length; i++) {
            this.add_log(data.results[i].remote_path, data.results[i].result, data.results[i].error);
            if (data.results[i].result == -1) {
                failed = true;
                break;
            }
        }

        if (failed) {
            this.set_progress(0);
            this.set_processed(1);
            this.set_status('failed');
            this.set_button_text('Start');
            clearInterval(this.timer);
        } else {
            this.offset += this.upload_files.length;
            this.set_progress((this.offset * 100 / files.length).toFixed(0));
            this.set_processed(this.offset);

            if (this.offset < this.files.length) {
                this.process();
            } else {
                this.set_status('done');
                this.set_button_text('Start');
                clearInterval(this.timer);
            }
        }
    },

    timer_callback: function() {
        this.seconds_elapsed++;
        this.set_elapsed(seconds_to_string(this.seconds_elapsed));
    },

    init: function(files, cdn_url) {
        if (files === undefined) {
            files = [];
        }

        this.files = files;

        var me = this;
        jQuery('#cdn_export_file_start').click(function() {
            if (this.value == 'Pause') {
                me.paused = 1;
                me.set_button_text('Resume');
                me.set_status('paused');
                clearInterval(me.timer);
            } else {
                if (this.value == 'Start') {
                    me.offset = 0;
                    me.seconds_elapsed = 0;
                    me.clear_log();
                    me.set_elapsed('-');
                }
                me.paused = 0;
                me.set_button_text('Pause');
                me.set_status('processing');
                me.timer = setInterval(function() {
                    me.timer_callback();
                }, 1000);
            }

            me.process();
        });
    }
};

var Cdn_Export_Library = {
    paused: 0,
    limit: 25,
    offset: 0,
    retry_seconds: 10,
    seconds_elapsed: 0,
    timer: null,

    set_progress: function(percent) {
        jQuery('#cdn_export_library_progress .progress-bar').width(percent + '%');
        jQuery('#cdn_export_library_progress .progress-value').html(percent + '%');
    },

    set_status: function(status) {
        jQuery('#cdn_export_library_status').html(status);
    },

    set_processed: function(processed) {
        jQuery('#cdn_export_library_processed').html(processed);
    },

    set_total: function(total) {
        jQuery('#cdn_export_library_total').html(total);
    },

    set_button_text: function(text) {
        jQuery('#cdn_export_library_start').val(text);
    },

    set_last_response: function() {
        var date = new Date();
        jQuery('#cdn_export_library_last_response').html(date.toLocaleTimeString() + ' ' + date.toLocaleDateString());
    },

    set_elapsed: function(text) {
        jQuery('#cdn_export_library_elapsed').html(text);
    },

    add_log: function(path, result, error) {
        jQuery('#cdn_export_library_log').prepend('<div class="log-' + (result == 1 ? 'success' : 'error') + '">' + path + ' <strong>' + error + '</strong></div>');
    },

    clear_log: function() {
        jQuery('#cdn_export_library_log').html('');
    },

    process: function() {
        if (this.paused) {
            return;
        }

        var me = this;
        jQuery.ajax( {
            type: 'POST',
            url: 'options-general.php?page=w3-total-cache/w3-total-cache.php',
            data: {
                w3tc_action: 'cdn_export_library_process',
                limit: this.limit,
                offset: this.offset
            },
            dataType: 'json',
            success: function(data) {
                me.set_last_response();
                me.process_callback(data);
            },
            error: function() {
                me.set_last_response();
                me.retry(me.retry_seconds);
            }
        });
    },

    retry: function(seconds) {
        if (this.paused) {
            return;
        }
        this.set_status('request failed (retry in ' + seconds + 's)');
        if (seconds) {
            var me = this;
            setTimeout(function() {
                me.retry(--seconds);
            }, 1000);
        } else {
            this.set_status('processing');
            this.process();
        }
    },

    process_callback: function(data, status) {
        this.offset += data.count;

        this.set_total(data.total);
        this.set_processed(this.offset);
        this.set_progress((this.offset * 100 / data.total).toFixed(0));

        var failed = false;
        for ( var i = 0; i < data.results.length; i++) {
            this.add_log(data.results[i].remote_path, data.results[i].result, data.results[i].error);
            if (data.results[i].result == -1) {
                failed = true;
                break;
            }
        }

        if (failed) {
            this.set_progress(0);
            this.set_processed(1);
            this.set_status('failed');
            this.set_button_text('Start');
            clearInterval(this.timer);
        } else {
            if (this.offset < data.total) {
                this.process();
            } else {
                this.set_status('done');
                this.set_button_text('Start');
                clearInterval(this.timer);
            }
        }
    },

    timer_callback: function() {
        this.seconds_elapsed++;
        this.set_elapsed(seconds_to_string(this.seconds_elapsed));
    },

    init: function() {
        var me = this;
        jQuery('#cdn_export_library_start').click(function() {
            if (this.value == 'Pause') {
                me.paused = 1;
                me.set_status('paused');
                me.set_button_text('Resume');
                clearInterval(me.timer);
            } else {
                if (this.value == 'Start') {
                    me.offset = 0;
                    me.seconds_elapsed = 0;
                    me.clear_log();
                    me.set_elapsed('-');
                }
                me.paused = 0;
                me.set_status('processing');
                me.set_button_text('Pause');
                me.timer = setInterval(function() {
                    me.timer_callback();
                }, 1000);
            }

            me.process();
        });
    }
};

var Cdn_Import_Library = {
    paused: 0,
    limit: 5,
    offset: 0,
    cdn_host: '',
    retry_seconds: 10,
    seconds_elapsed: 0,
    timer: null,

    set_progress: function(percent) {
        jQuery('#cdn_import_library_progress .progress-bar').width(percent + '%');
        jQuery('#cdn_import_library_progress .progress-value').html(percent + '%');
    },

    set_status: function(status) {
        jQuery('#cdn_import_library_status').html(status);
    },

    set_processed: function(processed) {
        jQuery('#cdn_import_library_processed').html(processed);
    },

    set_total: function(total) {
        jQuery('#cdn_import_library_total').html(total);
    },

    set_button_text: function(text) {
        jQuery('#cdn_import_library_start').val(text);
    },

    set_last_response: function() {
        var date = new Date();
        jQuery('#cdn_import_library_last_response').html(date.toLocaleTimeString() + ' ' + date.toLocaleDateString());
    },

    set_elapsed: function(text) {
        jQuery('#cdn_import_library_elapsed').html(text);
    },

    is_redirect_permanent: function() {
        return (jQuery('#cdn_import_library_redirect_permanent:checked').size() > 0);
    },

    is_redirect_cdn: function() {
        return (jQuery('#cdn_import_library_redirect_cdn:checked').size() > 0);
    },

    add_log: function(path, result, error) {
        jQuery('#cdn_import_library_log').prepend('<div class="log-' + (result == 1 ? 'success' : 'error') + '">' + path + ' <strong>' + error + '</strong></div>');
    },

    clear_log: function() {
        jQuery('#cdn_import_library_log').html('');
    },

    add_rule: function(src, dst) {
        if (/^https?:\/\//.test(src)) {
            return;
        }

        if (this.is_redirect_cdn()) {
            dst = 'http://' + (this.cdn_host ? this.cdn_host : document.location.host) + '/' + dst;
        } else {
            dst = '/' + dst;
        }

        if (src.indexOf('/') != 0) {
            src = '/' + src;
        }

        var rules = jQuery('#cdn_import_library_rules');
        rules.val(rules.val() + 'Redirect ' + (this.is_redirect_permanent() ? '302 ' : '') + src + ' ' + dst + '\r\n');
    },

    clear_rules: function() {
        jQuery('#cdn_import_library_rules').val('');
    },

    process: function() {
        if (this.paused) {
            return;
        }

        var me = this;
        jQuery.ajax( {
            type: 'POST',
            url: 'options-general.php?page=w3-total-cache/w3-total-cache.php',
            data: {
                w3tc_action: 'cdn_import_library_process',
                limit: this.limit,
                offset: this.offset
            },
            dataType: 'json',
            success: function(data) {
                me.set_last_response();
                me.process_callback(data);
            },
            error: function() {
                me.set_last_response();
                me.retry(me.retry_seconds);
            }
        });
    },

    retry: function(seconds) {
        if (this.paused) {
            return;
        }
        this.set_status('request failed (retry in ' + seconds + 's)');
        if (seconds) {
            var me = this;
            setTimeout(function() {
                me.retry(--seconds);
            }, 1000);
        } else {
            this.set_status('processing');
            this.process();
        }
    },

    process_callback: function(data) {
        this.offset += data.count;

        this.set_total(data.total);
        this.set_processed(this.offset);
        this.set_progress((this.offset * 100 / data.total).toFixed(0));

        var failed = false;
        for ( var i = 0; i < data.results.length; i++) {
            this.add_log(data.results[i].src, data.results[i].result, data.results[i].error);
            if (data.results[i].result == 1) {
                this.add_rule(data.results[i].src, data.results[i].dst);
            } else if (data.results[i].result == -1) {
                failed = true;
                break;
            }
        }

        if (failed) {
            this.set_progress(0);
            this.set_processed(1);
            this.set_status('failed');
            this.set_button_text('Start');
            clearInterval(this.timer);
        } else {
            if (this.offset < data.total) {
                this.process();
            } else {
                this.set_status('done');
                this.set_button_text('Start');
                clearInterval(this.timer);
            }
        }
    },

    timer_callback: function() {
        this.seconds_elapsed++;
        this.set_elapsed(seconds_to_string(this.seconds_elapsed));
    },

    init: function(cdn_host) {
        var me = this;
        this.cdn_host = cdn_host;
        jQuery('#cdn_import_library_start').click(function() {
            if (this.value == 'Pause') {
                me.paused = 1;
                me.set_button_text('Resume');
                me.set_status('paused');
                clearInterval(me.timer);
            } else {
                if (this.value == 'Start') {
                    me.offset = 0;
                    me.seconds_elapsed = 0;
                    me.clear_log();
                    me.clear_rules();
                    me.set_elapsed('-');
                }
                me.paused = 0;
                me.set_button_text('Pause');
                me.set_status('processing');
                me.timer = setInterval(function() {
                    me.timer_callback();
                }, 1000);
            }

            me.process();
        });
    }
};

var Cdn_Rename_Domain = {
    paused: 0,
    limit: 25,
    offset: 0,
    retry_seconds: 10,
    seconds_elapsed: 0,
    timer: null,

    set_progress: function(percent) {
        jQuery('#cdn_rename_domain_progress .progress-bar').width(percent + '%');
        jQuery('#cdn_rename_domain_progress .progress-value').html(percent + '%');
    },

    set_status: function(status) {
        jQuery('cdn_rename_domain_status').html(status);
    },

    set_processed: function(processed) {
        jQuery('#cdn_rename_domain_processed').html(processed);
    },

    set_total: function(total) {
        jQuery('#cdn_rename_domain_total').html(total);
    },

    set_button_text: function(text) {
        jQuery('#cdn_rename_domain_start').val(text);
    },

    set_last_response: function() {
        var date = new Date();
        jQuery('#cdn_rename_domain_last_response').html(date.toLocaleTimeString() + ' ' + date.toLocaleDateString());
    },

    set_elapsed: function(text) {
        jQuery('#cdn_rename_domain_elapsed').html(text);
    },

    add_log: function(path, result, error) {
        jQuery('#cdn_rename_domain_log').prepend('<div class="log-' + (result == 1 ? 'success' : 'error') + '">' + path + ' <strong>' + error + '</strong></div>');
    },

    clear_log: function() {
        jQuery('#cdn_rename_domain_log').html('');
    },

    get_domain_names: function() {
        return jQuery('#cdn_rename_domain_names').val();
    },

    process: function() {
        if (this.paused) {
            return;
        }

        var me = this;
        jQuery.ajax( {
            type: 'POST',
            url: 'options-general.php?page=w3-total-cache/w3-total-cache.php',
            data: {
                w3tc_action: 'cdn_rename_domain_process',
                names: this.get_domain_names(),
                limit: this.limit,
                offset: this.offset
            },
            dataType: 'json',
            success: function(data) {
                me.set_last_response();
                me.process_callback(data);
            },
            error: function() {
                me.set_last_response();
                me.retry(me.retry_seconds);
            }
        });
    },

    retry: function(seconds) {
        if (this.paused) {
            return;
        }
        this.set_status('request failed (retry in ' + seconds + 's)');
        if (seconds) {
            var me = this;
            setTimeout(function() {
                me.retry(--seconds);
            }, 1000);
        } else {
            this.set_status('processing');
            this.process();
        }
    },

    process_callback: function(data) {
        this.offset += data.count;

        this.set_total(data.total);
        this.set_processed(this.offset);
        this.set_progress((this.offset * 100 / data.total).toFixed(0));

        var failed = false;
        for ( var i = 0; i < data.results.length; i++) {
            this.add_log(data.results[i].old, data.results[i].result, data.results[i].error);
            if (data.results[i].result == -1) {
                failed = true;
                break;
            }
        }

        if (failed) {
            this.set_progress(0);
            this.set_processed(1);
            this.set_status('failed');
            this.set_button_text('Start');
            clearInterval(this.timer);
        } else {
            if (this.offset < data.total) {
                this.process();
            } else {
                this.set_status('done');
                this.set_button_text('Start');
                clearInterval(this.timer);
            }
        }
    },

    timer_callback: function() {
        this.seconds_elapsed++;
        this.set_elapsed(seconds_to_string(this.seconds_elapsed));
    },

    init: function(cdn_host) {
        var me = this;
        this.cdn_host = cdn_host;
        jQuery('#cdn_rename_domain_start').click(function() {
            if (this.value == 'Pause') {
                me.paused = 1;
                me.set_button_text('Resume');
                me.set_status('paused');
                clearInterval(me.timer);
            } else {
                if (this.value == 'Start') {
                    if (!me.get_domain_names()) {
                        alert('Empty domains to rename!');
                        return;
                    }
                    me.offset = 0;
                    me.seconds_elapsed = 0;
                    me.clear_log();
                    me.set_elapsed('-');
                }
                me.paused = 0;
                me.set_button_text('Pause');
                me.set_status('processing');
                me.timer = setInterval(function() {
                    me.timer_callback();
                }, 1000);
            }

            me.process();
        });
    }
};

jQuery(function($) {
    $('.tab').click(function() {
        $('.tab').removeClass('tab-selected');
        $('.tab-content').hide();
        $(this).addClass('tab-selected');
        $(this.rel).show();
    });

    $('.cdn_queue_delete').click(function() {
        return confirm('Are you sure you want to remove this file from the queue?');
    });

    $('.cdn_queue_empty').click(function() {
        return confirm('Are you sure you want to empty the queue?');
    });
});