$(function () {
  const roleLabels = { super_admin: 'Super Admin', admin: 'Admin', designer: 'Designer', viewer: 'Viewer' };
  let rows = {};

  new WB.Table({
    id: 'users', el: '#userTable', filters: '#userFilters', url: WB.url('users/list'), sort: 'created_at', empty: 'No users found.',
    columns: [
      { title: 'Name', sort: 'name', render: r => '<div class="fw-semibold">' + WB.esc(r.name) + (r.is_me ? ' <span class="badge text-bg-light border">You</span>' : '') + '</div><small class="text-muted">' + WB.esc(r.email) + '</small>' },
      { title: 'Role', sort: 'role', render: r => WB.badge(r.role, roleLabels[r.role]) },
      { title: 'Status', render: r => WB.badge(r.status) },
      { title: 'Last login', sort: 'last_login_at', render: r => WB.datetime(r.last_login_at) + (r.last_login_ip ? '<br><small class="text-muted">' + WB.esc(r.last_login_ip) + '</small>' : '') },
      { title: '', cls: 'text-end text-nowrap', render: r => {
          rows[r.id] = r;
          return '<button class="btn btn-sm btn-light" data-edit-user="' + r.id + '" title="Edit"><i class="bi bi-pencil"></i></button> ' +
            (r.is_me ? '' : '<button class="btn btn-sm btn-light text-danger" data-post="' + WB.url('users/' + r.id + '/delete') + '" data-reload-table="users" data-confirm="Delete the user ' + WB.esc(r.email) + '?" data-confirm-text="Delete" title="Delete"><i class="bi bi-trash"></i></button>');
        }
      }
    ]
  });

  $('#btnAddUser').on('click', function () {
    $('#userModal .pw-required').show();
    $('#userModal .pw-hint').text('At least 10 characters with letters and numbers.');
    WB.openForm('#userModal', { role: 'viewer', status: 'active' }, { action: WB.url('users'), title: 'Add user' });
  });

  $('#userTable').on('click', '[data-edit-user]', function () {
    const r = rows[$(this).data('edit-user')];
    $('#userModal .pw-required').hide();
    $('#userModal .pw-hint').text('Leave the password blank to keep the current one.');
    WB.openForm('#userModal', r, { action: WB.url('users/' + r.id), title: 'Edit user' });
  });
});
