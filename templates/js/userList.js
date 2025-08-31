let miniProgramUserDataTables;
$(function () {
  $('body').on('click', '#miniProgram-userList #userData tbody button', function () {
    const row = miniProgramUserDataTables.row($(this).closest('tr'));
    const data = row.data();
    $('#miniProgram-userList #modal-editUser #userForm').attr('action', basePath + '/admin/miniProgram/user/' + data.id).attr('method', 'PUT');
    $("#miniProgram-userList #modal-editUser #userForm input[name='name']").val(data.name);
    $("#miniProgram-userList #modal-editUser #userForm input[name='tel']").val(data.tel);
    $("#miniProgram-userList #modal-editUser #userForm select[name='status']").val(data.status);
    $('#miniProgram-userList #modal-editUser').modal('show');
  }).on('submit', '#miniProgram-userList #modal-editUser #userForm', function (e) {
    if (e.target.checkValidity()) {
      const fromData = new FormData(e.target);
      $.ajax({
        url: $(e.target).attr('action'),
        data: fromData,
        type: 'POST',
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-HTTP-Method-Override", $(e.target).attr('method'));
        },
        success: function () {
          const id = $(e).attr('action').split('/').pop();
          const data = miniProgramUserDataTables.row('#' + id).data();
          data['name'] = $("#userForm input[name='name']").val();
          data['tel'] = $("#userForm input[name='tel']").val();
          miniProgramUserDataTables.row('#' + id).data(data);
          $('#modal-editUser').modal('hide');
        },
        error: errorDialog
      });
    }
  });
});
