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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/* Biblio data export section */

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

    // create local function to fetch values
    function getValues($obj_db, $str_query)
    {
      // make query from database
      $_value_q = $obj_db->query($str_query);
      if ($_value_q->num_rows > 0) {
          $_value_buffer = '';
          while ($_value_d = $_value_q->fetch_row()) {
              if ($_value_d[0]) {
                  $_value_buffer .= '<'.$_value_d[0].'>';
              }
          }
          return $_value_buffer;
      }
      return null;
    }

    // limit
    $limit = intval($_POST['recordNum']);
    $offset = intval($_POST['recordOffset']);
    // fetch all data from biblio table
    $sql = "SELECT
        b.biblio_id, b.title, gmd.gmd_name, b.edition,
        b.isbn_issn, publ.publisher_name, b.publish_year,
        b.collation, b.series_title, b.call_number,
        lang.language_name, pl.place_name, b.classification,
        b.notes, b.image, b.sor
        FROM biblio AS b
        LEFT JOIN mst_gmd AS gmd ON b.gmd_id=gmd.gmd_id
        LEFT JOIN mst_publisher AS publ ON b.publisher_id=publ.publisher_id
        LEFT JOIN mst_language AS lang ON b.language_id=lang.language_id
        LEFT JOIN mst_place AS pl ON b.publish_place_id=pl.place_id ORDER BY b.last_update DESC";
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
      utility::jsToastr('Data Export', __('Error on query to database, Export FAILED!'), 'error');
    } else {
        if ($all_data_q->num_rows > 0) {
          $rows = [];
          $headers = null;

          while ($biblio_d = $all_data_q->fetch_assoc()) {
              array_walk($biblio_d, function(&$item, $key) { $item = trim( str_replace(array("\n", "\r"), '\\n', $item) ); });
              $id = $biblio_d['biblio_id'];

              // skip biblio_id
              unset($biblio_d['biblio_id']);

              // authors column
              $biblio_d['authors'] = getValues($dbs, 'SELECT a.author_name FROM biblio_author AS ba
              LEFT JOIN mst_author AS a ON ba.author_id=a.author_id
              WHERE ba.biblio_id='.$id)??'';

              // topics column
              $biblio_d['topics'] = getValues($dbs, 'SELECT t.topic FROM biblio_topic AS bt
              LEFT JOIN mst_topic AS t ON bt.topic_id=t.topic_id
              WHERE bt.biblio_id='.$id)??'';

              // item code column
              $biblio_d['item_code'] = getValues($dbs, 'SELECT item_code FROM item AS i
              WHERE i.biblio_id='.$id)??'';

              if ($headers === null) { $headers = array_keys($biblio_d); }
              $rows[] = array_values($biblio_d);
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
          header('Content-Disposition: attachment; filename="senayan_biblio_export.xlsx"');
          header('Cache-Control: max-age=0');
          (new Xlsx($spreadsheet))->save('php://output');
          exit();
        } else {
          utility::jsToastr('Data Export', __('There is no record in bibliographic database yet, Export FAILED!'), 'error');
        }
    }
  exit();
}
?>
<div class="menuBox">
<div class="menuBoxInner exportIcon">
	<div class="per_title">
    	<h2><?php echo __('Export Tool'); ?></h2>
	</div>
	<div class="infoBox">
    	<?php echo __('Export bibliographics data to XLSX file'); ?>
	</div>
</div>
</div>
<?php

// create new instance
$form = new simbio_form_table_AJAX('mainForm', $_SERVER['PHP_SELF'], 'post');
$form->submit_button_attr = 'name="doExport" value="'.__('Export Now').'" class="s-btn btn btn-default"';

// form table attributes
$form->table_attr = 'id="dataList" class="s-table table"';
$form->table_header_attr = 'class="alterCell font-weight-bold"';
$form->table_content_attr = 'class="alterCell2"';

/* Form Element(s) */
// number of records to export
$form->addTextField('text', 'recordNum', __('Number of Records To Export (0 for all records)'), '0', 'style="width: 10%;" class="form-control"');
// records offset
$form->addTextField('text', 'recordOffset', __('Start From Record'), '1', 'style="width: 10%;"  class="form-control"');
// header (column name)
$form->addCheckBox('header', __('Put columns names in the first row'), array( array('1', __('Yes')) ), '1');
// output the form
echo $form->printOut();
