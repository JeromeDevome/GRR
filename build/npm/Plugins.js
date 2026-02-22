'use strict'

const jslib = [
  // Clockpicker
  {
    from: 'node_modules/clockpicker/dist/',
    to: 'jslib/clockpicker'
  },
  // jQuery
  {
    from: 'node_modules/jquery/dist',
    to: 'jslib/jquery'
  },
  // jQuery-UI
  {
    from: 'node_modules/jquery-ui/dist',
    to: 'jslib/jquery-ui'
  },
  {
    from: 'node_modules/jquery-ui/ui/i18n',
    to: 'jslib/jquery-ui/ui/i18n'
  },
  // Popper
  {
    from: 'node_modules/popper.js/dist',
    to: 'jslib/popper'
  },
  // Bootstrap
  {
    from: 'node_modules/bootstrap/dist',
    to: 'jslib/bootstrap'
  },
  // Font Awesome
  {
    from: 'node_modules/@fortawesome/fontawesome-free/css',
    to: 'jslib/fontawesome-free/css'
  },
  {
    from: 'node_modules/@fortawesome/fontawesome-free/webfonts',
    to: 'jslib/fontawesome-free/webfonts'
  },
  // Chart.js
  {
    from: 'node_modules/chart.js/dist/',
    to: 'jslib/chart.js'
  },
  // Summernote
  {
    from: 'node_modules/summernote/dist/',
    to: 'jslib/summernote'
  },
  // Bootstrap Slider
  {
    from: 'node_modules/bootstrap-slider/dist/',
    to: 'jslib/bootstrap-slider'
  },
  {
    from: 'node_modules/bootstrap-slider/dist/css',
    to: 'jslib/bootstrap-slider/css'
  },
  // Moment
  {
    from: 'node_modules/moment/min',
    to: 'jslib/moment'
  },
  {
    from: 'node_modules/moment/locale',
    to: 'jslib/moment/locale'
  },
  // FastClick
  {
    from: 'node_modules/fastclick/lib',
    to: 'jslib/fastclick'
  },
  // Date Range Picker
  {
    from: 'node_modules/daterangepicker/',
    to: 'jslib/daterangepicker'
  },
  // DataTables
  {
    from: 'node_modules/pdfmake/build',
    to: 'jslib/pdfmake'
  },
  {
    from: 'node_modules/jszip/dist',
    to: 'jslib/jszip'
  },
  {
    from: 'node_modules/datatables.net/js',
    to: 'jslib/datatables'
  },
  {
    from: 'node_modules/datatables.net-bs4/js',
    to: 'jslib/datatables-bs4/js'
  },
  {
    from: 'node_modules/datatables.net-bs4/css',
    to: 'jslib/datatables-bs4/css'
  },
  {
    from: 'node_modules/datatables.net-autofill/js',
    to: 'jslib/datatables-autofill/js'
  },
  {
    from: 'node_modules/datatables.net-autofill-bs4/js',
    to: 'jslib/datatables-autofill/js'
  },
  {
    from: 'node_modules/datatables.net-autofill-bs4/css',
    to: 'jslib/datatables-autofill/css'
  },
  {
    from: 'node_modules/datatables.net-buttons/js',
    to: 'jslib/datatables-buttons/js'
  },
  {
    from: 'node_modules/datatables.net-buttons-bs4/js',
    to: 'jslib/datatables-buttons/js'
  },
  {
    from: 'node_modules/datatables.net-buttons-bs4/css',
    to: 'jslib/datatables-buttons/css'
  },
  {
    from: 'node_modules/datatables.net-colreorder/js',
    to: 'jslib/datatables-colreorder/js'
  },
  {
    from: 'node_modules/datatables.net-colreorder-bs4/js',
    to: 'jslib/datatables-colreorder/js'
  },
  {
    from: 'node_modules/datatables.net-colreorder-bs4/css',
    to: 'jslib/datatables-colreorder/css'
  },
  {
    from: 'node_modules/datatables.net-fixedcolumns/js',
    to: 'jslib/datatables-fixedcolumns/js'
  },
  {
    from: 'node_modules/datatables.net-fixedcolumns-bs4/js',
    to: 'jslib/datatables-fixedcolumns/js'
  },
  {
    from: 'node_modules/datatables.net-fixedcolumns-bs4/css',
    to: 'jslib/datatables-fixedcolumns/css'
  },
  {
    from: 'node_modules/datatables.net-fixedheader/js',
    to: 'jslib/datatables-fixedheader/js'
  },
  {
    from: 'node_modules/datatables.net-fixedheader-bs4/js',
    to: 'jslib/datatables-fixedheader/js'
  },
  {
    from: 'node_modules/datatables.net-fixedheader-bs4/css',
    to: 'jslib/datatables-fixedheader/css'
  },
  {
    from: 'node_modules/datatables.net-keytable/js',
    to: 'jslib/datatables-keytable/js'
  },
  {
    from: 'node_modules/datatables.net-keytable-bs4/js',
    to: 'jslib/datatables-keytable/js'
  },
  {
    from: 'node_modules/datatables.net-keytable-bs4/css',
    to: 'jslib/datatables-keytable/css'
  },
  {
    from: 'node_modules/datatables.net-responsive/js',
    to: 'jslib/datatables-responsive/js'
  },
  {
    from: 'node_modules/datatables.net-responsive-bs4/js',
    to: 'jslib/datatables-responsive/js'
  },
  {
    from: 'node_modules/datatables.net-responsive-bs4/css',
    to: 'jslib/datatables-responsive/css'
  },
  {
    from: 'node_modules/datatables.net-rowgroup/js',
    to: 'jslib/datatables-rowgroup/js'
  },
  {
    from: 'node_modules/datatables.net-rowgroup-bs4/js',
    to: 'jslib/datatables-rowgroup/js'
  },
  {
    from: 'node_modules/datatables.net-rowgroup-bs4/css',
    to: 'jslib/datatables-rowgroup/css'
  },
  {
    from: 'node_modules/datatables.net-rowreorder/js',
    to: 'jslib/datatables-rowreorder/js'
  },
  {
    from: 'node_modules/datatables.net-rowreorder-bs4/js',
    to: 'jslib/datatables-rowreorder/js'
  },
  {
    from: 'node_modules/datatables.net-rowreorder-bs4/css',
    to: 'jslib/datatables-rowreorder/css'
  },
  {
    from: 'node_modules/datatables.net-scroller/js',
    to: 'jslib/datatables-scroller/js'
  },
  {
    from: 'node_modules/datatables.net-scroller-bs4/js',
    to: 'jslib/datatables-scroller/js'
  },
  {
    from: 'node_modules/datatables.net-scroller-bs4/css',
    to: 'jslib/datatables-scroller/css'
  },
  {
    from: 'node_modules/datatables.net-searchbuilder/js',
    to: 'jslib/datatables-searchbuilder/js'
  },
  {
    from: 'node_modules/datatables.net-searchbuilder-bs4/js',
    to: 'jslib/datatables-searchbuilder/js'
  },
  {
    from: 'node_modules/datatables.net-searchbuilder-bs4/css',
    to: 'jslib/datatables-searchbuilder/css'
  },
  {
    from: 'node_modules/datatables.net-searchpanes/js',
    to: 'jslib/datatables-searchpanes/js'
  },
  {
    from: 'node_modules/datatables.net-searchpanes-bs4/js',
    to: 'jslib/datatables-searchpanes/js'
  },
  {
    from: 'node_modules/datatables.net-searchpanes-bs4/css',
    to: 'jslib/datatables-searchpanes/css'
  },
  {
    from: 'node_modules/datatables.net-select/js',
    to: 'jslib/datatables-select/js'
  },
  {
    from: 'node_modules/datatables.net-select-bs4/js',
    to: 'jslib/datatables-select/js'
  },
  {
    from: 'node_modules/datatables.net-select-bs4/css',
    to: 'jslib/datatables-select/css'
  },

  // icheck bootstrap
  {
    from: 'node_modules/icheck-bootstrap/',
    to: 'jslib/icheck-bootstrap'
  },
  // inputmask
  {
    from: 'node_modules/inputmask/dist/',
    to: 'jslib/inputmask'
  },
  // ion-rangeslider
  {
    from: 'node_modules/ion-rangeslider/',
    to: 'jslib/ion-rangeslider'
  },
  // jQuery Mousewheel
  {
    from: 'node_modules/jquery-mousewheel/',
    to: 'jslib/jquery-mousewheel'
  },
  // jQuery Knob
  {
    from: 'node_modules/jquery-knob-chif/dist/',
    to: 'jslib/jquery-knob'
  },
  // pace-progress
  {
    from: 'node_modules/@lgaitan/pace-progress/dist/',
    to: 'jslib/pace-progress'
  },
  // Select2
  {
    from: 'node_modules/select2/dist/',
    to: 'jslib/select2'
  },
  {
    from: 'node_modules/@ttskch/select2-bootstrap4-theme/dist/',
    to: 'jslib/select2-bootstrap4-theme'
  },
  // Select2 (Bootstrap 5 theme)
  {
    from: 'node_modules/select2-bootstrap-5-theme/dist/',
    to: 'jslib/select2-bootstrap-5-theme'
  },
  // Sparklines
  {
    from: 'node_modules/sparklines/source/',
    to: 'jslib/sparklines'
  },
  // Toastr
  {
    from: 'node_modules/toastr/build/',
    to: 'jslib/toastr'
  },
  // jsGrid
  {
    from: 'node_modules/jsgrid/dist',
    to: 'jslib/jsgrid'
  },
  {
    from: 'node_modules/jsgrid/demos/db.js',
    to: 'jslib/jsgrid/demos/db.js'
  },
  // bootstrap4-duallistbox
  {
    from: 'node_modules/bootstrap4-duallistbox/dist',
    to: 'jslib/bootstrap4-duallistbox/'
  },
  // ekko-lightbox
  {
    from: 'node_modules/ekko-lightbox/dist',
    to: 'jslib/ekko-lightbox/'
  },
  // jQuery Validate
  {
    from: 'node_modules/jquery-validation/dist/',
    to: 'jslib/jquery-validation'
  },
  // bs-custom-file-input
  {
    from: 'node_modules/bs-custom-file-input/dist/',
    to: 'jslib/bs-custom-file-input'
  },
  // bs-stepper
  {
    from: 'node_modules/bs-stepper/dist/',
    to: 'jslib/bs-stepper'
  },
  // dropzonejs
  {
    from: 'node_modules/dropzone/dist/',
    to: 'jslib/dropzone'
  },
  //ace editor
  {
    from: 'node_modules/ace-builds/src-min/ace.js',
    to: 'jslib/ace-editor/ace.js'
  },
  {
    from: 'node_modules/ace-builds/src-min/mode-css.js',
    to: 'jslib/ace-editor/mode-css.js'
  },
  {
    from: 'node_modules/ace-builds/src-min/theme-monokai.js',
    to: 'jslib/ace-editor/theme-monokai.js'
  },
  {
    from: 'node_modules/ace-builds/src-min/worker-css.js',
    to: 'jslib/ace-editor/worker-css.js'
  },
  // Floatthead
  {
    from: 'node_modules/floatthead/dist/',
    to: 'jslib/floatthead'
  },
  // PDFMake
  {
    from: 'node_modules/pdfmake/build/pdfmake.min.js',
    to: 'jslib/pdfmake/pdfmake.min.js'
  },
  {
    from: 'node_modules/pdfmake/build/vfs_fonts.js',
    to: 'jslib/pdfmake/vfs_fonts.js'
  },
]

module.exports = jslib
