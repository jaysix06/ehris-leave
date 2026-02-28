// DataTables configuration and setup
// This file initializes DataTables.net-vue3 and its dependencies
import JSZip from 'jszip';
import pdfMake from 'pdfmake';
import * as pdfFonts from 'pdfmake/build/vfs_fonts';

// Set up JSZip for Excel exports (required by DataTables buttons)
if (typeof window !== 'undefined') {
    (window as any).JSZip = JSZip;
}

// Set up pdfMake for PDF exports (required by DataTables buttons)
if (typeof pdfFonts.pdfMake !== 'undefined' && typeof pdfFonts.pdfMake.vfs !== 'undefined') {
    pdfMake.vfs = pdfFonts.pdfMake.vfs;
}

// Import DataTables and buttons extensions
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-dt';
import 'datatables.net-buttons';
import 'datatables.net-buttons/js/buttons.html5';
import 'datatables.net-buttons/js/buttons.print';

// Import styles
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net-buttons-dt/css/buttons.dataTables.css';

// Use DataTables core
DataTable.use(DataTablesCore);

export { DataTable, DataTablesCore };
