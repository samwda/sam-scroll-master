jQuery(document).ready(function ($) {
  if (typeof samsmAdmin === 'undefined') {
    return;
  }

  var $select = $('#samsm-excluded-pages');
  if ($select.length) {
    $select.select2({
      placeholder: "Search posts/pages",
      allowClear: true,
      ajax: {
        url: samsmAdmin.ajax_url,
        dataType: "json",
        delay: 250,
        data: function (params) {
          return {
            action: 'samsm_search_posts',
            nonce: samsmAdmin.nonce,
            q: params.term
          };
        },
        processResults: function (data) {
          return { results: data };
        },
        cache: true
      },
      width: 'resolve'
    });
  }
});
