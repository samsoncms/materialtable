<?php
namespace samson\cms\web\materialtable;

use samson\activerecord\dbQuery;
use samson\pager\Pager;

/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 02.12.2014 at 12:41
 */

class MaterialTableTable extends \samson\cms\table\Table
{
    /** Table template */
    public $table_tmpl = 'table/index';

    /** Table row template */
    public $row_tmpl = 'table/row';

    public $dbQuery;

    /** Existing CMSMaterial field records */
    private $fields = array();

    /** Pointer to CMSMaterial */
    private $material;

    /** @var \samson\cms\Navigation Pointer to current table structure */
    private $structure;

    /** Fields locale */
    private $locale;

    /** @var string $renderModule Module to render */
    protected $renderModule = 'material_table';

    /**
     * Constructor
     * @param \samson\cms\CMSMaterial $material Current material object
     * @param Pager $pager Pager object
     * @param \samson\cms\Navigation Current table structure object
     * @param string $locale Locale string
     */
    public function __construct(\samson\cms\CMSMaterial & $material, Pager $pager = null, $structure, $locale = 'ru')
    {
        $this->dbQuery = new dbQuery();
        // Retrieve pointer to current module for rendering
        $this->renderModule = & s()->module($this->renderModule);

        // Set input locale as current
        $this->locale = $locale;

        // Save pointer to CMSMaterial
        $this->material = & $material;

        // Get all table materials identifiers from current form material
        $tableMaterialIds = dbQuery('material')
            ->cond('parent_id', $this->material->id)
            ->cond('type', 3)
            ->cond('Active', 1)
            ->cond('Draft', 0)
            ->join('structurematerial')
            ->cond('structurematerial.StructureID', $structure->StructureID)
            ->fieldsNew('MaterialID');

        // Set current table structure as input structure
        $this->structure = $structure;

        // Get all fields of table structure
        dbQuery('field')->join('structurefield')->cond('StructureID', $structure->StructureID)->exec($structureFields);

        /** @var \samson\cms\CMSField $field Field object */
        foreach ($structureFields as $field) {

            // Add field to fields collection
            $this->fields[$field->id] = $field;

            /** @var int $materialId Table material identifier */
            foreach ($tableMaterialIds as $materialId) {

                // If such materialfield (Table cell) doesn't exist
                if (!dbQuery('materialfield')
                    ->cond('MaterialID', $materialId)
                    ->cond('locale', $this->locale)
                    ->cond('FieldID', $field->id)
                    ->first()) {

                    // If locale is set and field is localized
                    // Or if locale is not set and isn't localized
                    if (($locale != '' && $field->local == 1 || $locale == '' && $field->local == 0)) {

                        /** @var \samson\activerecord\materialfield $materialField Create material field record */
                        $materialField = new \samson\activerecord\materialfield(false);
                        $materialField->MaterialID = $materialId;
                        $materialField->FieldID = $field->id;
                        $materialField->Active = 1;
                        // Set materialfield locale if locale is set and field is localized
                        if ($locale != '' && $field->local == 1) {
                            $materialField->locale = $this->locale;
                        }
                        // Write it to DataBase
                        $materialField->save();
                    }
                }
            }
        }

        // Get all table materials
        $this->query = dbQuery('material')
            ->cond('parent_id', $this->material->id)
            ->cond('type', 3)
            ->order_by('priority')
            ->join('materialfield');
        // With specified fields if they exist
        if (!empty($this->fields)) {
            $this->query->cond('materialfield.FieldID', array_keys($this->fields));
        }

        // Constructor treed
        parent::__construct( $this->query );
    }

    /**
     * Function to view table row
     * @param \samson\activerecord\material $material Tale material represented as row
     * @param Pager $pager Pager for multi page table
     * @return string
     */
    public function row(& $material, Pager & $pager = null, $module = NULL)
    {
        /** @var string $tdHTML Table cell HTML code */
        $tdHTML = '';
        $input = null;

        /** @var \samson\cms\CMSField $field Field of current table structure (Table column) */
        foreach ($this->fields as $field) {
            /** @var \samson\activerecord\materialfield $materialField Materialfield object (Table cell) */
            foreach ($material->onetomany['_materialfield'] as $materialField) {
                // If materialfield relates to field (column) and has same locale or doesn't have it
                if ($materialField->FieldID == $field->FieldID &&
                    ($materialField->locale == $this->locale || ($field->local == 0 && $materialField->locale == ''))) {

                    if ($field->Type < 8) {
                        $input = m('samsoncms_input_application')
                            ->createFieldByType($this->dbQuery, $field->Type, $materialField);
                    }
                    if ($field->Type == 4) {
                        /** @var \samsoncms\input\select\Select $input Select input type */
                        $input->build();
                    }

                    // Set HTML row code
                    $tdHTML .= $this->renderModule->view('table/tdView')->set($input, 'input')->output();
                    break;
                }
            }
        }

        // Render field row
        return $this->renderModule
            ->view($this->row_tmpl)
            ->set(m('samsoncms_input_text_application')->createField($this->dbQuery, $material, 'Url'), 'materialName')
            ->set('materialID', $material->id)
            ->set('parentID', $material->parent_id)
            ->set('structureId', $this->structure->StructureID)
            ->set('td_view', $tdHTML)
            ->set($pager, 'pager')
            ->output();
    }

    /**
     * Function to render table
     * @param array $dbRows Set of table materials
     * @param null $module
     * @return string Table HTML
     */
    public function render(array $dbRows = null, $module = null)
    {
        // Rows HTML
        $rows = '';

        // if no rows data is passed - perform db request
        if(!isset($dbRows)) {
            $this->query->exec($dbRows);
        }

        // If we have table rows data
        if (is_array($dbRows)) {

            // Save quantity of rendering rows
            $this->last_render_count = sizeof($dbRows);

            // Iterate db data and perform rendering
            foreach($dbRows as & $dbRow) {
                $rows .= $this->row( $dbRow, $this->pager );
            }
        }
        // No data found after query, external render specified
        else $rows .= $this->emptyrow($this->query, $this->pager );

        // Columns headers HTML
        $thHTML = '';

        /** @var \samson\cms\CMSField $field Table structure fields */
        foreach ($this->fields as $field) {
            $thHTML .= $this->renderModule->view('table/thView')->set('fieldName', $field->Description)->output();
        }

        // If there is some data in table
        if ($rows != '') {
            // Render table view
            return $this->renderModule
                ->view($this->table_tmpl)
                ->set('thView', $thHTML)
                ->set( $this->pager )
                ->set('rows', $rows)
                ->output();
        } else {
            return '';
        }
    }
}
