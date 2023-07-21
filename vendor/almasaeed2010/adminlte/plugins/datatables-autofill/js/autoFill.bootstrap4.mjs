/*! Bootstrap integration for DataTables' AutoFill
 * ©2015 SpryMedia Ltd - datatables.net/license
 */

import jQuery from 'jquery';
import DataTable from 'datatables.net-bs4';
import AutoFill from 'datatables.net-autofill';

// Allow reassignment of the $ variable
let $ = jQuery;


DataTable.AutoFill.classes.btn = 'btn btn-primary';


export default DataTable;
