/* Website admin: shared behaviour (logout, dashboards) */
(function ($) {
  'use strict';
  $(document).on('click', '[data-admin-logout]', function (e) {
    e.preventDefault();
    WB.post(WB.url('logout')).always(function () { window.location.href = WB.url('login'); });
  });
})(jQuery);
