<?php
namespace samson\cms\web\materialtable;

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
    public function row(& $material, Pager & $pager = null )
    {
        /** @var string $tdHTML Table cell HTML code */
        $tdHTML = '';

        /** @var \samson\cms\CMSField $field Field of current table structure (Table column) */
        foreach ($this->fields as $field) {
            /** @var \samson\activerecord\materialfield $materialField Materialfield object (Table cell) */
            foreach ($material->onetomany['_materialfield'] as $materialField) {
                // If materialfield relates to field (column) and has same locale or doesn't have it
                if ($materialField->FieldID == $field->FieldID &&
                    ($materialField->locale == $this->locale || ($field->local == 0 && $materialField->locale == ''))) {

                    // Depending on field type
                    switch ($field->Type) {
                        case '4':
                            /** @var \samson\cms\input\Select $input Select field type */
                            $input = \samson\cms\input\Field::fromObject($materialField, 'Value', 'Select');
                            $input->optionsFromString($materialField->Value);
                            break;
                        case '1':
                            /** @var \samson\cms\input\File $input File field type */
                            $input = \samson\cms\input\Field::fromObject($materialField, 'Value', 'File');
                            break;
                        case '3':
                            /** @var \samson\cms\input\Date $input Date field type */
                            $input = \samson\cms\input\Field::fromObject($materialField, 'Value', 'Date');
                            break;
                        case '6':
                            /** @var \samson\cms\input\Material $input Material field type */
                            $input = \samson\cms\input\Field::fromObject($materialField, 'Value', 'Material');
                            break;
                        case '7':
                            /** @var \samson\cms\input\Field $input Numeric filed type */
                            $input = \samson\cms\input\Field::fromObject($materialField, 'numeric_value', 'Field');
                            break;
                        default :
                            /** @var \samson\cms\input\Field $input Text filed type */
                            $input = \samson\cms\input\Field::fromObject($materialField, 'Value', 'Field');
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
            ->set(\samson\cms\input\Field::fromObject($material, 'Url', 'Field'), 'materialName')
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
