/**
* Created by omelchenko on 03.12.2014.
*/

function bindButtons(tab, response){
    tab.html(response.table);
    s('#material-tabs').tabs();
    SamsonCMS_InputField(s('.__inputfield.__textarea'));
    initMaterialTable(tab);
    initSort();
    loader.hide();
}

function initMaterialTable(tab){
    s('.delete_table_material', tab).ajaxClick(function (response) {
        bindButtons(tab, response);
    }, function(){
        loader.show('', true);
        return true;
    });
    s('.material_table_add', tab).ajaxClick(function (response) {
        bindButtons(tab, response);
    }, function(){
        loader.show('', true);
        return true;
    });
    s('.copy_table_material', tab).ajaxClick(function (response) {
        bindButtons(tab, response);
    }, function(){
        loader.show('', true);
        return true;
    });
}

s('.tab-group-content').pageInit(function(tab){
    initMaterialTable(tab);
    initSort();
});

function initSort() {
    $('.material_table_table tbody').each(function (idx, table) {
        table = $(table);
        table.sortable({
            axis: 'y',
            scroll: true,
            cursor: 'move',
            containment: 'parent',
            delay: 150,
            stop: function () {
                loader.show('', true);
                if (table.attr('__action_priority')) {
                    var priorityUrl = table.attr('__action_priority')
                } else {
                    console.error('No priority URL was set, please add "__action_priority" attribute and proper URL to table');
                }
                var ids = [];
                table.find('tr').each(function (idx, row) {
                    row = $(row);
                    if (row.attr('row_id')) {
                        ids[idx] = row.attr('row_id');
                    }
                });
                $.ajax({
                    url: priorityUrl,
                    type: 'POST',
                    async: true,
                    data: {ids: ids},
                    headers: {
                        'SJSAsync': 'true'
                    },
                    success: function () {
                        loader.hide();
                    }
                });
            }
        });
    });
}