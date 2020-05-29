//include "core/dcms.js"
DCMS.UserUpdate = {
        fields: ['mail_new_count', 'friend_new_count'],
        errors: 0,
        time_last: 0,
        id_user: null,
        timeout: null,
        interval: 7,
        delay_update: function(sec) {
            sec = sec || this.interval;
            var self = this;
            self.stop();
            self.timeout = setTimeout(function() {
                self.update.call(self);
            }, sec * 1000);
        },
        update: function() {
            var self = this;
            DCMS.Ajax({
                url: '/ajax/user.json.php?' + this.fields.join('&'),
                callback: function() {
                    self.onresult.apply(self, arguments);
                },
                error: function() {
                    self.onerror.call(self);
                }
            })
        },
        onerror: function() {
            this.errors++;
            this.delay_update(30 * this.errors);
        },
        onresult: function(data) {
            DCMS.Event.trigger('user_update', JSON.parse(data));
            this.errors = 0;
            this.delay_update(this.interval);
        },
        stop: function() {
            if (this.timeout)
                clearTimeout(this.timeout);
        }
    }