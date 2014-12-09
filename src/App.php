<?php
namespace samson\cms\web\materialtable;

use samson\activerecord\material;
use samson\activerecord\structure;
use samson\cms\input\Date;

/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 02.12.2014 at 12:03
 */

class App extends \samson\cms\App
{
    /** Application name */
    public $name;

    /** Hide application access from main menu */
    public $hide = true;

    /** Identifier */
    protected $id = 'material_table';

    protected $form;

    /** @see \samson\core\ExternalModule::init() */
    public function prepare( array $params = null )
    {
        // TODO: Change this logic to make tab loading more simple
        // Create new materialtable tabs object to load it
        //class_exists( ns_classname('MaterialTableTabLocalized','samson\cms\web\materialtable') );
        class_exists( ns_classname('BuildTab','samson\cms\web\materialtable') );
    }

    /**
     * @param $materialId int Material identifier
     * @param $structureId int Structure identifier
     * @return array AJAX response
     */
    public function __async_addpopup($materialId, $structureId) {
        return array('status' => 1, 'popup' => m('material_table')
            ->view('popup/add_popup')
            ->set('materialId', $materialId)
            ->set('structureId', $structureId)
            ->output());
    }

    /**
     * Creating new material table row
     * @param int $materialId This material identifier
     * @param int $structureId Table structure identifier
     * @return array AJAX response
     */
    public function __async_add($materialId, $structureId) {
        /** @var \samson\cms\CMSMaterial $material */
        $material = null;
        if (dbQuery('\samson\cms\CMSMaterial')->cond('MaterialID', $materialId)->first($material)) {
            $structures = $material->cmsnavs();
            if (!empty($structures)) {
                /** @var \samson\cms\Navigation $structure Table structure */
                foreach ($structures as $structure) {
                    if ($structure->StructureID == $structureId) {
                        $tableMaterial = new \samson\cms\CMSMaterial(false);
                        $tableMaterial->type = 3;
                        $tableMaterial->Name = $structure->Name;
                        $tableMaterial->Url = $structure->Name . '-' . md5(date('Y-m-d-h-i-s'));
                        $tableMaterial->parent_id = $material->MaterialID;
                        $tableMaterial->Published = 1;
                        $tableMaterial->Active = 1;
                        $tableMaterial->save();
                    }
                }
                return array('status' => 1);
            }
        }
        return array('status' => 0);
    }

    /**
     * Controller for deleting row from material table
     * @param string $id Table material identifier
     * @return array Async response array
     */
    public function __async_delete($id)
    {
        // Async response
        $result = array( 'status' => false );

        /** @var \samson\cms\CMSMaterial $material */
        $material = null;

//        if (ifcmsmat($id, $material, 'MaterialID')) {
//            $material->delete();
//            $result['status'] = true;
//        }
        if( dbQuery('\samson\cms\CMSMaterial')->id($id)->first($material)) {
            $material->deleteWithFields();
//            var_dump($material);
            $result['status'] = true;
        }

        return $result;
    }

    /**
     * Async updating material table
     * @param $parentID
     * @return array
     */
    public function __async_table($parentID)
    {
        $form = new \samson\cms\web\material\Form($parentID);

        /** @var MaterialTableTabLocalized $tab */
        $tab = new MaterialTableTabLocalized($form);

        $content = $tab->getContent();

        return array('status' => 1, 'table' => $content);
    }

    public function getMaterialTableTable($materialId, $structureId, $locale = '')
    {
        /** @var \samson\cms\CMSMaterial $material */
        $material = dbQuery('\samson\cms\CMSMaterial')->cond('MaterialID', $materialId)->first();

        $table = new MaterialTableTable($material, $structureId, $locale);

        $all = false;
        $multilingual = false;

        if (dbQuery('\samson\cms\CMSNavMaterial')
            ->cond('MaterialID', $materialId)
            ->join('structure')
            ->cond('StructureID', $structureId)
            ->first()) {

            // Check with locales we have in fields table
            if (dbQuery('structurefield')->cond('StructureID', $structureId)
                ->join('field')->cond('field_local', 0)->first()) {
                $all = true;
            }
            if (dbQuery('structurefield')->cond('StructureID', $structureId)
                ->join('field')->cond('field_local', 1)->first()) {
                $multilingual = true;
            }
        }

        if (($locale == '' && $all) || ($locale != '' && $multilingual)) {
            return m('material_table')->view('tab_view')
                ->set('table', $table->render())
                ->set('materialId', $materialId)
                ->set('structureId', $structureId)
                ->output();
        }
        return '';
    }
}