function format(d) {
  const formatted = d.keywordEnumValueList.map(item => {
    return `<br>${item.keywordCode}[${item.enumValueList.join("、")}]`;
  }).join("；");
  return '<div class="row"><div class="col-sm-6">' + d.content + '</div><div class="col-sm-6">' + d.example + formatted + '</div></div>';
}

$(function () {
  const subscribeMessageDataTables = $('#subscribeMessageData').DataTable({
    serverSide: false,
    ajax: basePath + "/admin/miniProgram/subscribeMessage",
    rowId: 'id',
    columns: [
      {
        "class": "details-control text-center",
        "orderable": false,
        "data": null,
        "defaultContent": '<i class="fas fa-plus-circle"></i>'
      },
      {
        title: '模板ID', data: "priTmplId"
      },
      {title: '模板标题', data: "title"},
      {
        title: '类型', data: "type", render: function (data) {
          return data === 2 ? '一次性订阅' : '长期订阅'
        }
      },
      {
        title: '操作',
        data: "op",
        defaultContent: '<button type="button" class="btn btn-tool del"><i class="fas fa-trash-alt"></i></button>'
      }
    ]
  });

  let detailRows = [];

  $('body').on('click', '#wx-subscribeMessage #subscribeMessageData tbody td.details-control', function () {
    let tr = $(this).closest('tr');
    let row = subscribeMessageDataTables.row(tr);
    let idx = $.inArray(tr.attr('id'), detailRows);

    if (row.child.isShown()) {
      $(this).html('<i class="fas fa-plus-circle"></i>');
      row.child.hide();

      detailRows.splice(idx, 1);
    } else {
      $(this).html('<i class="fas fa-minus-circle"></i>');
      row.child(format(row.data())).show();
      if (idx === -1) {
        detailRows.push(tr.attr('id'));
      }
    }
  }).on('click', '#wx-subscribeMessage #subscribeMessageData tbody button', function () {
    const row = $(this).closest('tr');
    const data = subscribeMessageDataTables.row(row).data();
    console.log(data);
    dialog('删除订阅消息模板', '是否确认删除消息模板', function () {
      $.ajax({
        url: basePath + '/admin/miniProgram/subscribeMessage/' + data.priTmplId,
        type: 'POST',
        headers: {"X-HTTP-Method-Override": "DELETE"},
        dataType: 'json',
        success: function () {
          subscribeMessageDataTables.row(row).remove().draw(false);
          Swal.fire({icon: 'success', title: '删除成功！', showConfirmButton: false, timer: 1500});
        },
        error: errorDialog
      });
    });
  });

  subscribeMessageDataTables.on('draw', function () {
    $.each(detailRows, function (i, id) {
      $('#' + id + ' td.details-control').trigger('click');
    });
  });
});