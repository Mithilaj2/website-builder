/* Website admin: email settings and administrators (owner only) */
(function ($) {
  'use strict';
  $('#smtpOn').on('change', function () { $('#smtpFields').toggleClass('d-none', !this.checked); });

  new WB.Table({
    id: 'users', el: '#userTable', url: WB.url('api/users'), perPage: 100, empty: 'No administrators.',
    columns: [
      { title: 'Name', render: r => WB.esc(r.name) + (r.is_me ? ' <span class="badge text-bg-light border">You</span>' : '') + '<div class="small text-muted">' + WB.esc(r.email) + '</div>' },
      { title: 'Role', render: r => '<span class="badge text-bg-' + (r.role === 'owner' ? 'primary' : 'info') + '">' + (r.role === 'owner' ? 'Owner' : 'Editor') + '</span>' + (r.status !== 'active' ? ' <span class="badge text-bg-secondary">Inactive</span>' : '') },
      { title: '', cls: 'text-end text-nowrap', render: r => '<button class="btn btn-sm btn-light" data-edit-user=\'' + WB.esc(JSON.stringify(r)) + '\' aria-label="Edit"><i class="bi bi-pencil"></i></button>' +
          (r.is_me ? '' : ' <button class="btn btn-sm btn-light text-danger" data-post="' + WB.url('api/users/' + r.id + '/delete') + '" data-confirm="Remove ' + WB.esc(r.name) + ' as an administrator?" data-confirm-text="Remove" aria-label="Delete"><i class="bi bi-trash"></i></button>') }
    ]
  });

  $('#btnAddUser').on('click', function () {
    WB.openForm('#userModal', { role: 'editor', status: 'active' }, { action: WB.url('api/users'), title: 'Add administrator' });
    $('#userModal .pw-hint').text('At least 10 characters with letters and numbers. Share it with them securely.');
  });
  $(document).on('click', '[data-edit-user]', function () {
    const u = $(this).data('edit-user');
    WB.openForm('#userModal', u, { action: WB.url('api/users/' + u.id), title: 'Edit administrator' });
    $('#userModal .pw-hint').text('Leave empty to keep the current password.');
  });
})(jQuery);
