/**
* Created by omelchenko on 03.12.2014.
*/

function bindButtons(tab, response){
    tab.html(response.table);
    s('#material-tabs').tabs();
    SamsonCMS_InputField(s('.__inputfield.__textarea'));
    initMaterialTable(tab);
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
}

s('.tab-group-content').pageInit(function(tab){
    initMaterialTable(tab);
});