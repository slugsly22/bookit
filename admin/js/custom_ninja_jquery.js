if(typeof Marionette !== 'undefined') {
    var mySubmitController = Marionette.ItemView.extend( {
        initialize: function() {
            // init listener
            this.listenTo( Backbone.Radio.channel( 'forms' ), 'view:show', this.initAction);
            // field change listener
            this.listenTo( Backbone.Radio.channel( 'fields' ), 'change:modelValue', this.valueChanged);
            console.log(Backbone.Radio.channel( 'fields' ));
            // submit listener
            this.listenTo( Backbone.Radio.channel( 'forms' ), 'submit:response', this.actionSubmit );
        },

        // init action
        initAction: function() {
            console.log("init");
        },

        // input update action
        valueChanged: function(model) {
            console.log("update");
        },

        // submit action
        actionSubmit: function( response ) {
            // handled via php

            console.log("submit");
        },
    });

    // initialise listening controller for ninja form
    new mySubmitController();
}
