(function ($, Drupal, debounce) {

  Drupal.behaviors.bookishAdminImageWidget = {
    attach: function attach(context, settings) {
      // This is insane, but I'm trying to work around a graphical bug where
      // image previews would flash on the screen since core AJAX animations
      // are really lacking.
      $('.bookish-image-preview', context).once('bookish-image-preview').each(function () {
        $(this).parent().css('position', 'relative');
        $clone = $(this).clone();
        $clone.attr('id', '');
        $clone.attr('class', 'bookish-image-preview-clone');
        $clone.attr('style', '');
        $(this).before($clone);
        $(this).promise().done(function(){
          $(this).parent().find('.bookish-image-preview-clone').each(function() {
            if (!$(this).is($clone)) {
              $oldClone = $(this);
              setTimeout(function () {
                $oldClone.remove();
              }, 1000);
            }
          });
        });
      });

      $('.bookish-image-data-container', context).once('bookish-image-container').each(function () {
        $(this).find('input[type="range"]').each(function () {
          var $resetButton = $('<button class="bookish-image-reset"><span class="visually-hidden">Reset</span></button>');
          var $range = $(this);
          $resetButton.click(function(e) {
            e.preventDefault();
            $range.val(0);
            $range.trigger('change');
          });
          $(this).after($resetButton);

          $(this).on('input', debounce(function () {
            $(this).trigger('change');
          }, 100));
        });
      });
      $('.bookish-image-focal-point-container', context).once('bookish-image-focal-point').each(function() {
        var $dot = $('<div class="bookish-image-focal-point-dot"></div>');
        var $img = $(this).find('img');
        $dot.css('left', $img.width() / 2);
        $dot.css('top', $img.height() / 2);
        $(this).append($dot);
        var $container = $(this);
        $img.click(function (e) {
          var x = e.pageX - $img.offset().left;
          var y = e.pageY - $img.offset().top;
          var differenceX = $img.attr('width') / $img.width();
          var differenceY = $img.attr('height') / $img.height();
          $dot.css('left', x);
          $dot.css('top', y);
          $container.parent()
            .find('.bookish-image-focal-point-input')
            .val(Math.round(x * differenceX) + ',' + Math.round(y * differenceY));
          $container.parent()
          .find('.bookish-image-re-render')
          .click();
        });
      });
    }
  };

  var beforeSend = Drupal.Ajax.prototype.beforeSend;

  Drupal.Ajax.prototype.beforeSend = function (xmlhttprequest, options) {
    if (!$(this.element).is('.bookish-image-data-container input[type="range"]')) {
      beforeSend.call(this, xmlhttprequest, options);
      return;
    }

    // For forms without file inputs, the jQuery Form plugin serializes the
    // form values, and then calls jQuery's $.ajax() function, which invokes
    // this handler. In this circumstance, options.extraData is never used. For
    // forms with file inputs, the jQuery Form plugin uses the browser's normal
    // form submission mechanism, but captures the response in a hidden IFRAME.
    // In this circumstance, it calls this handler first, and then appends
    // hidden fields to the form to submit the values in options.extraData.
    // There is no simple way to know which submission mechanism will be used,
    // so we add to extraData regardless, and allow it to be ignored in the
    // former case.
    if (this.$form) {
      options.extraData = options.extraData || {};

      // Let the server know when the IFRAME submission mechanism is used. The
      // server can use this information to wrap the JSON response in a
      // TEXTAREA, as per http://jquery.malsup.com/form/#file-upload.
      options.extraData.ajax_iframe_upload = '1';

      // The triggering element is about to be disabled (see below), but if it
      // contains a value (e.g., a checkbox, textfield, select, etc.), ensure
      // that value is included in the submission. As per above, submissions
      // that use $.ajax() are already serialized prior to the element being
      // disabled, so this is only needed for IFRAME submissions.
      const v = $.fieldValue(this.element);
      if (v !== null) {
        options.extraData[this.element.name] = v;
      }
    }

    // Disable the element that received the change to prevent user interface
    // interaction while the Ajax request is in progress. ajax.ajaxing prevents
    // the element from triggering a new request, but does not prevent the user
    // from changing its value.
    // $(this.element).prop('disabled', true);

    if (!this.progress || !this.progress.type) {
      return;
    }

    // Insert progress indicator.
    const progressIndicatorMethod = `setProgressIndicator${this.progress.type
      .slice(0, 1)
      .toUpperCase()}${this.progress.type.slice(1).toLowerCase()}`;
    if (
      progressIndicatorMethod in this &&
      typeof this[progressIndicatorMethod] === 'function'
    ) {
      this[progressIndicatorMethod].call(this);
    }
  };

})(jQuery, Drupal, Drupal.debounce);
