<?php
namespace samson\cms\web\materialtable;
use samson\cms\web\materialtable\tab\MaterialTable;
use samsoncms\app\material\Form;
use samsonphp\event\Event;

/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 02.12.2014 at 12:03
 */

class App extends \samsoncms\Application
{
    /** Application name */
    public $name;

    /** Hide application access from main menu */
    public $hide = true;

    /** Identifier */
    protected $id = 'material_table';

    /** @see \samson\core\ExternalModule::init() */
    public function prepare(array $params = null)
    {
        Event::subscribe('samsoncms.input.material.confirm', array($this, 'inputConfirm'));
        Event::subscribe('samsoncms.material.form.created', array($this, 'tabBuilder'));

        return parent::prepare($params);
    }

    public function tabBuilder(\samsoncms\app\material\form\Form & $form)
    {
        /** @var \samsoncms\api\Material $material Material instance */
        $material = null ;
        if ($this->query->entity('\samsoncms\api\Material')
            ->cond('MaterialID', $form->entity->id)
            ->first($material)) {
            /**@var array $structureIDs Try to get related structures to this material */
            $structureIDs = array();
            if ($this->query->entity('\samson\activerecord\structurematerial')
                ->cond('MaterialID', $material->id)
                ->fields('StructureID', $structureIDs)) {
                /** @var \samsoncms\api\Navigation $navigationTable Try to get navigation table records */
                foreach ($this->query->entity('\samsoncms\api\Navigation')
                    ->cond('StructureID', $structureIDs)
                    ->cond('Type', 2)
                    ->exec() as $navigationTable) {
                    $form->tabs[] = new MaterialTable($this, $this->query, $material, $navigationTable);
                }
            }
        }
    }

    public function inputConfirm($id)
    {
        $mf = dbQuery('materialfield')->cond('MaterialFieldID', $id)->first();
        $material = dbQuery('material')->cond('MaterialID', $mf->MaterialID)->first();
        $material->Active = 1;
        $material->save();
    }

    /**
     * Get max priority by current structure
     * @param $materialId
     * @param $structureId
     * @return int
     */
    public function getMaxPriority($materialId, $structureId)
    {
        // Get all table materials identifiers from current form material
        $tableMaterialIds = dbQuery('material')
            ->cond('parent_id', $materialId)
            ->cond('type', 3)
            ->cond('Active', 1)
            ->cond('Draft', 0)
            ->join('structurematerial')
            ->cond('structurematerial.StructureID', $structureId)
            ->fields('MaterialID');

        $material = null;
        if (
            dbQuery('material')
                ->cond('MaterialID', $tableMaterialIds)
                ->cond('Active', 1)
                ->order_by('priority', 'DESC')
                ->join('materialfield')
                ->first($material)
        ) {
            return $material->priority;
        }
        return 0;

    }

    /**
     * Update quantity fields table row
     * @param int $structureId Current table structure identifier
     * @param int $entityID Current table entity identifier
     * @return array AJAX response
     */

    public function __async_quantityFieldsRow($structureId, $entityID)
    {
        $structureFields = null;

        // Get all fields of table structure
        if ($this->query->className('field')
            ->join('structurefield')
            ->cond('StructureID', $structureId)
            ->exec($structureFields)
        ) {

            $fields = array();

            /** @var \samson\cms\CMSField $field Field object */
            foreach ($structureFields as $field) {
                // Add field to fields collection
                $fields[$field->id] = $field;
            }

            $countOfFields = $this->query->className('material')
                ->cond('parent_id', $entityID)
                ->cond('type', 3)
                ->cond('materialfield.FieldID', array_keys($fields))
                ->cond('Active', 1)
                ->join('materialfield')
                ->group_by('MaterialID')
                ->count();

            return array('status' => 1, 'countOfFields' => $countOfFields);
        } else {
            return array('status' => 0);
        }
    }

    /**
     * Creating new material table row
     * @param int $materialId Current material identifier
     * @param int $structureId Current table structure identifier
     * @param $afterAction
     * @param $afterMaterialId
     * @param $afterStructureId
     * @return array AJAX response
     */
    public function __async_add($materialId, $structureId, $afterAction, $afterMaterialId, $afterStructureId)
    {
        /** @var \samsoncms\api\Material $material Current material object */
        $material = null;

        // If there are no such row yet
        if ($this->query->className('\samsoncms\api\Material')->cond('MaterialID', $materialId)->first($material)) {

            /** @var array $structures Array of structures of this material */
            $structureIds = $this->query->className('structurematerial')->cond('MaterialID', $material->id)->fields('StructureID');
            $structures = $this->query->className('structure')->cond('StructureID', $structureIds)->exec();

            // If there are some structures
            if (!empty($structures)) {

                /** @var \samsoncms\api\Navigation $structure Table structure */
                foreach ($structures as $structure) {

                    // If current material has incoming structure
                    // TODO: Sometimes array has empty values not object - needs to checked
                    if (isset($structure->StructureID) && ($structure->StructureID == $structureId)) {

                        /** @var \samson\social\Core $socialModule Social module object */
                        $socialModule = m('social');
                        /** @var \samson\activerecord\user $user User object */
                        $user = $socialModule->user();

                        // Get max priority of this structure
                        $maxPriority = $this->getMaxPriority($materialId, $structureId);

                        /** @var \samsoncms\api\Material $tableMaterial New table material (table row) */
                        $tableMaterial = new \samsoncms\api\Material(false);
                        $tableMaterial->type = 3;
                        $tableMaterial->Name = $structure->Name;
                        $tableMaterial->Url = $structure->Name . '-' . md5(date('Y-m-d-h-i-s'));
                        $tableMaterial->parent_id = $material->MaterialID;
                        $tableMaterial->Published = 1;
                        $tableMaterial->Active = 1;
                        $tableMaterial->priority = $maxPriority+1;
                        $tableMaterial->UserID = $user->id;
                        $tableMaterial->Created = date('Y-m-d H:m:s');
                        $tableMaterial->Modyfied = $tableMaterial->Created;
                        $tableMaterial->save();

                        /** @var \samson\cms\CMSNavMaterial $structureMaterial Relation between created table material
                         * and table structure */
                        $structureMaterial = new \samson\cms\CMSNavMaterial(false);
                        $structureMaterial->StructureID = $structureId;
                        $structureMaterial->MaterialID  = $tableMaterial->MaterialID;
                        $structureMaterial->Active = 1;
                        $structureMaterial->save();
                    }
                }

                $result = $this->__async_table($afterMaterialId, $afterStructureId);

                Event::fire('samson.cms.web.materialtable.add', array($material->id, $structureId));
                // Set success status and return result
//                return array('status' => 1);
                return $result;
            }
        }
        // Set fail status and return result
        return array('status' => 0);
    }

    /**
     * Controller for deleting row from material table
     * @param string $id Table material identifier
     * @param $afterAction
     * @param $afterMaterialId
     * @param $afterStructureId
     * @return array Async response array
     */
    public function __async_delete($id, $afterAction, $afterMaterialId, $afterStructureId)
    {
        /** @var array $result Async response */
        $result = array( 'status' => false );

        /** @var \samsoncms\api\Material $material */
        $material = null;

        // If such material exists
        if ($this->query->className('\samsoncms\api\Material')->id($id)->first($material)) {
            // Delete this table material with it's all relations to structures and fields
            $material->deleteWithRelations();

            Event::fire('samson.cms.web.materialtable.delete', array($material->parent_id));
            // Set success status
//            $result['status'] = true;

            $result = $this->__async_table($afterMaterialId, $afterStructureId);
        }
        // Return result
        return $result;
    }

    /**
     * Controller for copying row from material table
     * @param string $id Table material identifier
     * @param $afterAction
     * @param $afterMaterialId
     * @param $afterStructureId
     * @return array Async response array
     */
    public function __async_copy($id, $afterAction, $afterMaterialId, $afterStructureId)
    {
        /** @var array $result Async response */
        $result = array( 'status' => false );

        /** @var \samsoncms\api\Material $material */
        $material = null;

        // If such material exists
        if ($this->query->className('\samsoncms\api\Material')->id($id)->first($material)) {
            // Make copy of this material
            /** @var \samsoncms\api\Material $copy Copy of existing material */
            $copy = $material->copy();
            $copy->save();
            // Set success status
            $result['status'] = true;

            $result = $this->__async_table($afterMaterialId, $afterStructureId);
        }
        // Return result
        return $result;
    }

    /**
     * Async updating material table
     * @param int $materialId Current material identifier
     * @param int $structureId Current table structure identifier
     * @return array Asynchronous response
     */
    public function __async_table($materialId, $structureId)
    {
        /** @var array $result Asynchronous response */
        $result = array('status' => false);

        /** @var \samsoncms\api\Navigation $structure Current table structure */
        $structure = $this->query->className('\samsoncms\api\Navigation')->cond('StructureID', $structureId)->first();
        $material = $this->query->className('\samson\cms\Material')->cond('MaterialID', $materialId)->first();

        // If such structure exists
        if (isset($structureId)) {
            /** @var MaterialTableTabLocalized $tab New tab with updated table */
            $tab = new MaterialTable($this, $this->query->className('\samsoncms\api\Material'), $material, $structure);

            // Get HTML code of this tab
            $content = $tab->content();
            
            // If need change content
            Event::fire('samson.cms.web.materialtable.get.table', array($materialId, $structureId, & $content));
            
            // Set success status and generated HTML
            $result['status'] = true;
            $result['table'] = $content;
        }

        // Return result of this controller
        return $result;
    }

    public function __async_priority()
    {
        /** @var array $result Asynchronous controller result */
        $result = array('status' => true);

        // If we have changed priority of rows
        if (isset($_POST['ids'])) {
            // For each received row id
            for ($i = 1; $i <= count($_POST['ids']); $i++) {

                /** @var \samson\activerecord\material $material Variable to store material */
                $material = null;
                // If we have such material in database
                if ($this->query->className('material')->cond('MaterialID', $_POST['ids'][$i])->first($material)) {
                    // Reset it's priority and save it
                    $material->priority = $i;
                    $material->save();
                } else {
                    $result['status'] = false;
                    $result['message'] = 'Can not find materials with specified ids!';
                }
            }
        } else {
            $result['status'] = false;
            $result['message'] = 'There are no materials to sort!';
        }
        return $result;
    }

    /**
     * Function to generate new table
     * @param int $materialId Current material identifier
     * @param \samsoncms\api\Navigation $structure Current table structure
     * @param string $locale Current locale
     * @return string Generated table HTML
     */
    public function getMaterialTableTable($materialId, $structure, $locale = '')
    {
        /** @var \samsoncms\api\Material $material Current material object */
        $material = null;

        // If material was found by identifier
        if ($this->query->className('\samsoncms\api\Material')->cond('MaterialID', $materialId)->first($material)) {

            /** @var MaterialTableTable $table Current table object */
            $table = new MaterialTableTable($material, null, $structure, $locale);

            /** @var boolean $all Flag to determine non-localized tab */
            $all = false;
            /** @var boolean $multilingual Flag to determine localized tab */
            $multilingual = false;

            // If there is relation between current material and current table structure
            if ($this->query->className('\samson\cms\CMSNavMaterial')
                ->cond('MaterialID', $materialId)
                ->join('structure')
                ->cond('StructureID', $structure->StructureID)
                ->first()
            ) {

                // If table structure has at least one none localized field
                if ($this->query->className('structurefield')->cond('StructureID', $structure->StructureID)
                    ->join('field')->cond('field_local', 0)->first()
                ) {
                    // Set non-localized flag to true
                    $all = true;
                }
                // If table structure has at least one localized field
                if ($this->query->className('structurefield')->cond('StructureID', $structure->StructureID)
                    ->join('field')->cond('field_local', 1)->first()
                ) {
                    // Set localized flag to true
                    $multilingual = true;
                }
            }

            // If locale is not set and none localized flag is true
            // Or if locale is set and localized flag is true
            // Render table to get it's HTML code
            //if (($locale == '' && $all) || ($locale != '' && $multilingual)) {
                return $this->view('tab_view')
                    ->set($table->render(), 'table')
                    ->set($materialId, 'materialId')
                    ->set($structure->StructureID, 'structureId')
                    ->output();
            //}
        }
        // Return empty string on fail
        return '';
    }
}
