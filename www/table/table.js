/**
* Created by omelchenko on 03.12.2014.
*/

var MTLoader = new Loader(s('#material-table-tab'));
function initMTTable(table) {
    s('.delete_table_material', table).each(function(link) {
        link.ajaxClick(function(response) {
            s('#material-table-tab-tab').html(response.table);
            s('#material-tabs').tabs();
            s('.material_table_table', response.table).each(function(table) {
                initMTTable(table);
            });
            SamsonCMS_InputField(s('.__inputfield.__textarea'));
            initMTAddButton();
            MTLoader.hide();
        }, function() {
            MTLoader.show('', true);
            return true;
        })
    });
}

s(document).pageInit(function(table) {
    s('.material_table_table').each(function(table) {
        initMTTable(table);
    });
    initMTAddButton();

});

function initMTAddButton()
{
    s('.material_table_add').each(function(link) {
        link.ajaxClick(function(response){
            MTLoader.hide();
            s('#material-table-tab-tab').html(response.table);
            s('#material-tabs').tabs();
            s('.material_table_table', response.table).each(function(table) {
                initMTTable(table);
            });
            SamsonCMS_InputField(s('.__inputfield.__textarea'));
            initMTAddButton();
        }, function(){
            MTLoader.show('', true);
            return true;
        });
    });
}