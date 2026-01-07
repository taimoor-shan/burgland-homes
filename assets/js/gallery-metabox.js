jQuery(function($) {
  'use strict';

  var file_frame;

  // Add images to gallery
  $(document).on('click', '.bh-gallery-add', function(e) {
    e.preventDefault();

    var button = $(this);

    if (file_frame) {
      file_frame.close();
    }

    file_frame = wp.media.frames.file_frame = wp.media({
      title: button.data('uploader-title'),
      button: {
        text: button.data('uploader-button-text'),
      },
      multiple: true
    });

    file_frame.on('select', function() {
      var listIndex = $('#bh-gallery-metabox-list li').length,
          selection = file_frame.state().get('selection');

      selection.map(function(attachment, i) {
        attachment = attachment.toJSON();
        var index = listIndex + i;
        var thumbnailUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
        
        var html = '<li class="bh-gallery-item">' +
          '<input type="hidden" name="bh_gallery_ids[' + index + ']" value="' + attachment.id + '">' +
          '<div class="bh-gallery-image-wrapper">' +
            '<img class="bh-image-preview" src="' + thumbnailUrl + '" alt="">' +
            '<div class="bh-gallery-item-actions">' +
              '<a class="bh-change-image button button-small" href="#" ' +
                 'data-uploader-title="Change Image" ' +
                 'data-uploader-button-text="Select Image" ' +
                 'title="Change image">' +
                '<span class="dashicons dashicons-update"></span>' +
              '</a>' +
              '<a class="bh-remove-image button button-small" href="#" title="Remove image">' +
                '<span class="dashicons dashicons-trash"></span>' +
              '</a>' +
              '<a class="bh-preview-image button button-small" href="' + attachment.url + '" target="_blank" title="Preview image">' +
                '<span class="dashicons dashicons-visibility"></span>' +
              '</a>' +
            '</div>' +
            '<div class="bh-gallery-drag-handle" title="Drag to reorder">' +
              '<span class="dashicons dashicons-move"></span>' +
            '</div>' +
          '</div>' +
        '</li>';
        
        $('#bh-gallery-metabox-list').append(html);
      });
      
      makeSortable();
    });

    file_frame.open();
  });

  // Change single image
  $(document).on('click', '.bh-change-image', function(e) {
    e.preventDefault();

    var button = $(this);
    var listItem = button.closest('li');

    if (file_frame) {
      file_frame.close();
    }

    file_frame = wp.media.frames.file_frame = wp.media({
      title: button.data('uploader-title'),
      button: {
        text: button.data('uploader-button-text'),
      },
      multiple: false
    });

    file_frame.on('select', function() {
      var attachment = file_frame.state().get('selection').first().toJSON();
      var thumbnailUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
      
      listItem.find('input[type="hidden"]').val(attachment.id);
      listItem.find('img.bh-image-preview').attr('src', thumbnailUrl);
      listItem.find('.bh-preview-image').attr('href', attachment.url);
    });

    file_frame.open();
  });

  // Remove image from gallery
  $(document).on('click', '.bh-remove-image', function(e) {
    e.preventDefault();

    if (!confirm('Are you sure you want to remove this image?')) {
      return;
    }

    $(this).closest('li').animate({ opacity: 0 }, 200, function() {
      $(this).remove();
      resetIndex();
    });
  });

  // Reset indexes after reordering or removing
  function resetIndex() {
    $('#bh-gallery-metabox-list li').each(function(i) {
      $(this).find('input[type="hidden"]').attr('name', 'bh_gallery_ids[' + i + ']');
    });
  }

  // Make gallery sortable
  function makeSortable() {
    $('#bh-gallery-metabox-list').sortable({
      opacity: 0.6,
      cursor: 'move',
      handle: '.bh-gallery-drag-handle',
      placeholder: 'bh-gallery-placeholder',
      stop: function() {
        resetIndex();
      }
    });
  }

  // Initialize sortable on page load
  makeSortable();

  // Prevent links from being clicked during drag
  $('#bh-gallery-metabox-list').on('click', 'a', function(e) {
    if ($(this).closest('li').hasClass('ui-sortable-helper')) {
      e.preventDefault();
    }
  });

});
