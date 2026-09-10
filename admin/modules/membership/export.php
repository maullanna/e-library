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

/* Member data export section */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// key to authenticate
define('INDEX_AUTH', '1');

// main system configuration
require '../../../sysconfig.inc.php';
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-membership');
// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require MDLBS . '/bibliography/biblio_utils.inc.php';

// privileges checking
$can_read = utility::havePrivilege('membership', 'r');
$can_write = utility::havePrivilege('membership', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

if (isset($_POST['doExport'])) {
    // set PHP time limit
    set_time_limit(3600);
    // limit
    $limit = intval($_POST['recordNum']);
    $offset = intval($_POST['recordOffset']);
    // fetch all data from member table
    $sql = "SELECT
        m.member_id, m.member_name, m.gender,
        mt.member_type_name, m.member_email, m.member_address,
        m.postal_code, m.inst_name, m.is_new,
        m.member_image, m.pin, m.member_phone,
        m.member_fax, m.member_since_date, m.register_date,
        m.expire_date, m.birth_date, m.member_notes
        FROM member AS m
        LEFT JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id ";
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
        utility::jsAlert(__('Error on query to database, Export FAILED!'));
    } else {
        if ($all_data_q->num_rows > 0) {
            $rows = [];
            $headers = null;
            while ($member_data = $all_data_q->fetch_assoc()) {
                if ($headers === null) { $headers = array_keys($member_data); }
                $rows[] = array_values($member_data);
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
            header('Content-Disposition: attachment; filename="senayan_member_export.xlsx"');
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
            exit();
        } else {
            utility::jsAlert(__('There is no record in membership database yet, Export FAILED!'));
        }
    }
    exit();
}

?>
<div class="menuBox">
<div class="menuBoxInner exportIcon">
	<div class="per_title">
    	<h2><?php echo __('Export Data'); ?></h2>
    </div>
    <div class="infoBox">
    	<?php echo __('Export member(s) data to XLSX file'); ?>
    </div>
</div>
</div>
<?php

// create new instance
$form = new simbio_form_table_AJAX('mainForm', $_SERVER['PHP_SELF'].'', 'post');
$form->submit_button_attr = 'name="doExport" value="'.__('Export Now').'" class="s-btn btn btn-primary"';

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
