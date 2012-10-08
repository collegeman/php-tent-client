/**
 * JS for our Console app.
 * @license MIT
 */
(function($) {

  prettyPrint();

  var $form = $('form');
  var $fields = $('#fields'), clipboard;
  var $fieldTemplate = $('#field-template');
  var method = 'GET';
  var $entity = $('#entity');
  var $path = $('#path');
  var $btnLogin = $form.find('[data-action="login"]');
  var $btnLogout = $form.find('[data-action="logout"]');
  var $btnSubmit = $form.find('[data-action="request"]');
  
  var request = function(action, method, data, callback) {
    if ($.isFunction(method)) {
      callback = method;
      data = {};
      method = 'GET';
    } else if ($.isFunction(data)) {
      callback = data;
      data = {};
    }

    var type = method;
    if (method !== 'GET' && method !== 'POST') {
      type = 'POST';
    }

    data = data || {};
    data.action = action;

    $.ajax({
      url: 'api.php',
      'type': type,
      dataType: 'json',
      beforeSend: function(xhr) {
        if (method !== 'GET' && method !== 'POST') {
          xhr.setRequestHeader('X-HTTP-Method-Override', method);
        }
      },
      data: data,
      success: callback || function() {},
      error: function(response) {
        $('.alert-error').text(response.error).html(
          '<b>Oops!</b> ' + $('.alert-error').text()
        );
        if (callback) {
          callback(response);
        }
      }
    });
  };

  $btnLogin.on('click', function() {
    $btnLogin.button('loading');
    request('login', 'POST', { 
      entity: $entity.val(), 
      redirect_uri: config.redirect_uri 
    }, function(response) {
      if (response && response.url) {
        document.location = response.url;
      }
    });
    return false;
  });

  $btnLogout.on('click', function() {
    request('logout', 'POST', function() {
      document.location.reload();
    });
    return false;
  });

  var $btnAddField = $form.find('[data-action="add-field"]').on('click', function() {
    var $field = $fieldTemplate.find('div:first').clone();
    $field.find('input:first').attr('name', 'name[]');
    $field.find('input:last').attr('name', 'value[]');
    $field.find('[data-action="remove-field"]').on('click', function() {
      $(this).parent().remove();
      return false;
    });
    $fields.append($field);
    return false;
  });

  var $setReqMethodBtns = $form.find('[data-action="set-req-method"]');
  $setReqMethodBtns.on('click', function() {
    var $this = $(this);
    method = $this.data('req-method');
    $('[data-label-for="req-method"]').text(method);
    $setReqMethodBtns.find('.icon-ok').css({ 'visibility': 'hidden' });
    $this.find('.icon-ok').css({ 'visibility': 'visible' });

    if (method === 'GET') {
      clipboard = $fields.children().clone(true, true);
      $fields.empty();
      $btnAddField.attr('disabled', true);
    } else {
      if (clipboard) {
        $fields.append(clipboard);
        clipboard = false;
      }
      $btnAddField.attr('disabled', false);
    } 
  });

  $path.data('width', $path.width());
  var $btnServer = $form.find('[data-toggle="server"]').on('click', function() {
    $this = $(this);
    var $span = $this.find('span').toggle();
    fixPathField();
    $this.find('i').removeClass().addClass($span.is(':visible') ? 'icon-arrow-left' : 'icon-arrow-right');
    $path.focus();
    return false;
  });

  var fixPathField = function() {
    var margin = $btnServer.outerWidth();
    $path.css({ 'paddingLeft': 10 + margin });
    $path.width( $path.data('width') - margin - 10 );
  }
  fixPathField();

  $path.keyup(function(e) {
    if (e.keyCode === 13) {
      $btnSubmit.trigger('click');
      e.stopPropagation();
      e.preventDefault();
      return false;
    }
    $btnSubmit.attr('disabled', $path.val().trim().length < 1);
  });

  $entity.keyup(function(e) {
    if (e.keyCode === 13) {
      $btnLogin.trigger('click');
      e.stopPropagation();
      e.preventDefault();
      return false;
    }
    $btnLogin.attr('disabled', $entity.val().trim().length < 1);
  });

  $btnSubmit.click(function(e) {
    $this = $(this);
    $this.button('loading');
    request('request', 'POST', {
      /*
      path: $path.val(), 
      method: method
      */
      path: 'posts',
      method: 'POST',
      data: {
        "type": "https://tent.io/types/post/status/v0.1.0",
        "published_at": 1348806667,
        "permissions": {
          "public": true
        },
        "licenses": [
          "http://creativecommons.org/licenses/by/3.0/"
        ],
        "content": {
          "text": "Just landed.",
          "location": {
            "type": "Point",
            "coordinates": [
              50.923878,
              4.028605
            ]
          }
        }
        
      }
    }, function(response) {
      $this.button('reset');
      $('#response').html(JSON.stringify(response, null, '  '));
      prettyPrint();
    });
  });

  $form.submit(function() {
    return false;
  });

  if (!$entity.val().trim()) {
    $entity.focus();
  } else {
    $path.focus();
  }

})(jQuery);
