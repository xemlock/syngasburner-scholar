jQuery(function() {
  jQuery('input[type=text]').each(function() {
    if (this.name == 'start_date' || this.name == 'end_date') {
      this.style.width = '100px';     
      pickDate(this);
      this.onkeyup = function() {
        if (this.value.length == 10) {
          var v = this.value
          var y = v.substr(0, 4);
          var m = v.substr(5, 2);
          var d = v.substr(8, 2);
          this.value = y + '-' + m + '-' + d;
        }
      }
    }
  })
});

function configureDatepicker() {
  if (typeof configuredDatepicker != 'undefined') return;
  jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false, dateFormat: 'yy-mm-dd', 
      showButtonPanel: true, 
      showOn: 'focus', 
      // buttonImage: 'images/calendar.gif', buttonImageOnly: false, 
      // buttonText: 'Wybierz datê', 
      showAnim: 'slideDown', 
      speed: 'fast',
      beforeShow:function() {
        this.className += ' input-focus';
    }, onClose:function() {
      _openedCalendarInput = null;
      this.className = this.className.replace(/input-focus/,''); 
    }
  }, jQuery.datepicker.regional[window.lang]));
  configuredDatepicker = true;
}

function pickDate(input) { // triggerem jest dowolny element
  configureDatepicker();
  var jInput = jQuery(input);
  jInput.datepicker();
  jInput.unbind('focus');

  input.onfocus = function() {
    // according to: http://code.google.com/p/jquery-datepicker/issues/detail?id=43
    // setting zIndex is required here!
    jQuery('#ui-datepicker-div')[0].style.zIndex = 9999999999;
    jInput.datepicker('show');
    _openedCalendarInput = jInput;
    return false;
  }
  // to przechwytuje 'Today' i zamyka kalendarz
  // input.onblur = function() { try { jInput.datepicker('hide'); } catch (e) {} }
  input.setAttribute('maxlength', 10);

  jQuery('#ui-datepicker-div').draggable();
  // trigger.onclick();
}
