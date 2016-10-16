(function() {
    var Pitchoun = {
        init: function() {
            if ($('#result').length > 0) {
                this.selectResult();
            }
        },

        selectResult: function() {
            $('input#super-tiny-url').focus(function(){
                this.select();
            });

            $('input#super-tiny-url').focus();
        }
    };

    $(document).ready(function(){
        Pitchoun.init();
    });
}());
