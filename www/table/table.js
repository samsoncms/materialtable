/**
* Created by omelchenko on 03.12.2014.
*/

var RelatedMaterialLoader = new Loader(s('#material-table-tab'));
function initRelatedTable(table) {
    s('.delete_table_material', table).each(function(link) {
        link.ajaxClick(function(response) {
            s('#material-table-tab-tab').html(response.table);
            s('#material-tabs').tabs();
            s('.material_table_table', response.table).each(function(table) {
                initRelatedTable(table);
            });
            SamsonCMS_InputField(s('.__inputfield.__textarea'));
            initAddButton();
            RelatedMaterialLoader.hide();
        }, function() {
            RelatedMaterialLoader.show('', true);
            return true;
        })
    });
}

s(document).pageInit(function(table) {
    s('.material_table_table').each(function(table) {
        initRelatedTable(table);
    });
    initAddButton();

});

function initAddButton()
{
    s('.material_table_add').each(function(link) {
        link.tinyboxAjax({
            html : 'popup',
            oneClickClose : true,
            renderedHandler : function(form, tb) {
                s('.add_material_table_form', form).ajaxSubmit(function(response) {
                    RelatedMaterialLoader.hide();
                    s('#material-table-tab-tab').html(response.table);
                    s('#material-tabs').tabs();
                    s('.material_table_table', response.table).each(function(table) {
                        initRelatedTable(table);
                    });
                    SamsonCMS_InputField(s('.__inputfield.__textarea'));
                    initAddButton();
                }, function() {
                    RelatedMaterialLoader.show('', true);
                    tb._close();
                    return true;
                });
            },
            beforeHandler : function() {
                RelatedMaterialLoader.show('', true);
                return true;
            },
            responseHandler : function() {
                RelatedMaterialLoader.hide();
                return true;
            }
        });
    });
}