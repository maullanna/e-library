<?php
/**
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */


/* Item data export section */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// key to authenticate
define('INDEX_AUTH', '1');

// main system configuration
require '../../../sysconfig.inc.php';
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');
// start the session
require SB.'admin/default/session.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';
require __DIR__ . '/biblio_utils.inc.php';

// privileges checking
$can_read = utility::havePrivilege('bibliography', 'r');
$can_write = utility::havePrivilege('bibliography', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You are not authorized to view this section').'</div>');
}

if (isset($_POST['doExport'])) {
    // set PHP time limit
    set_time_limit(0);

    // limit
    $limit = intval($_POST['recordNum']);
    $offset = intval($_POST['recordOffset']);
    // fetch all data from item table
    $sql = "SELECT
        i.item_code, i.call_number, ct.coll_type_name,
        i.inventory_code, i.received_date, spl.supplier_name,
        i.order_no, loc.location_name,
        i.order_date, st.item_status_name, i.site,
        i.source, i.invoice, i.price, i.price_currency, i.invoice_date,
        i.input_date, i.last_update, b.title
        FROM item AS i
        LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
        LEFT JOIN mst_coll_type AS ct ON i.coll_type_id=ct.coll_type_id
        LEFT JOIN mst_supplier AS spl ON i.supplier_id=spl.supplier_id
        LEFT JOIN mst_item_status AS st ON i.item_status_id=st.item_status_id
        LEFT JOIN mst_location AS loc ON i.location_id=loc.location_id ";
    if ($limit > 0) { $sql .= ' LIMIT '.$limit; }
    if ($offset > 1) {
        if ($limit > 0) {
            $sql .= ' OFFSET '.($offset-1);
        } else {
            $sql .= ' LIMIT '.($offset-1).',99999999999';
        }
    }
    // for debugging purpose only
    // die($sql);
    $all_data_q = $dbs->query($sql);
    if ($dbs->error) {
        utility::jsToastr('Item Export', __('Error on query to database, Export FAILED!'.$dbs->error), 'error');
    } else {
        if ($all_data_q->num_rows > 0) {
            $rows = [];
            $headers = null;
            while ($item_d = $all_data_q->fetch_assoc()) {
                if ($headers === null) { $headers = array_keys($item_d); }
                $rows[] = array_values($item_d);
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $rowNum = 1;
            if (isset($_POST['header'])) {
                $sheet->fromArray($headers, null, 'A1');
                $rowNum++;
            }
            $sheet->fromArray($rows, null, 'A'.$rowNum);
            styleExportSheet($sheet, count($rows[0] ?? $headers ?? []), isset($_POST['header']));

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="senayan_item_export.xlsx"');
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
            exit();
        } else {
            utility::jsToastr('Item Export', __('There is no record in item database yet, Export FAILED!'), 'error');
        }
    }
    exit();
}
?>
<div class="menuBox">
<div class="menuBoxInner exportIcon">
	<div class="per_title">
    	<h2><?php echo __('Item Export Tool'); ?></h2>
	</div>
	<div class="infoBox">
    <?php echo __('Export item data to XLSX file'); ?>
	</div>
</div>
</div>
<?php

// create new instance
$form = new simbio_form_table_AJAX('downloadForm', $_SERVER['PHP_SELF'], 'post');
$form->submit_button_attr = 'name="doExport" data-filename="senayan_item_export.xlsx" value="'.__('Export Now').'" class="s-btn btn btn-default"';

// form table attributes
$form->table_attr = 'id="dataList" class="s-table table"';
$form->table_header_attr = 'class="alterCell font-weight-bold"';
$form->table_content_attr = 'class="alterCell2"';

/* Form Element(s) */
// number of records to export
$form->addTextField('text', 'recordNum', __('Number of Records To Export (0 for all records)'), '0', 'style="width: 10%;" class="form-control"');
// records offset
$form->addTextField('text', 'recordOffset', __('Start From Record'), '1', 'style="width: 10%;" class="form-control"');
// header (column name)
$form->addCheckBox('header', __('Put columns names in the first row'), array( array('1', __('Yes')) ), '1');
// output the form
echo $form->printOut();
