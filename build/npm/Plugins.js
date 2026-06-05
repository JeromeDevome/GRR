'use strict'

const jslib = [
  // Clockpicker
  {
    from: 'node_modules/clockpicker/dist/',
    to: 'jslib/clockpicker',
    filterName: '.min'
  },
  // jQuery
  {
    from: 'node_modules/jquery/dist',
    to: 'jslib/jquery',
    filterName: 'jquery.min.js'
  },
  // jQuery-UI
  {
    from: 'node_modules/jquery-ui/dist',
    to: 'jslib/jquery-ui',
    filterName: 'jquery-ui.min.'
  },
  {
    from: 'node_modules/jquery-ui/ui/i18n',
    to: 'jslib/jquery-ui/ui/i18n'
  },
  // jQuery Validation
  {
    from: 'node_modules/jquery-validation/dist',
    to: 'jslib/jquery-validation',
    filterName: '.min'
  },
  // jQuery Validation (localization)
  {
    from: 'node_modules/jquery-validation/dist/localization',
    to: 'jslib/jquery-validation/localization',
    filterName: '.min'
  },
  // Bootstrap
  {
    from: 'node_modules/bootstrap/dist',
    to: 'jslib/bootstrap',
    filterName: '.min.',
    excludeName: '.map'
  },
  // Font Awesome
  {
    from: 'node_modules/@fortawesome/fontawesome-free/css',
    to: 'jslib/fontawesome-free/css',
    filterName: '.min'
  },
  {
    from: 'node_modules/@fortawesome/fontawesome-free/webfonts',
    to: 'jslib/fontawesome-free/webfonts'
  },
  // Summernote
  {
    from: 'node_modules/summernote/dist/font',
    to: 'jslib/summernote/font',
  },
  {
    from: 'node_modules/summernote/dist/plugin',
    to: 'jslib/summernote/plugin',
  },
  {
    from: 'node_modules/summernote/dist/',
    to: 'jslib/summernote',
    filterName: '.min',
    excludeName: ['.map', 'summernote-bs']
  },
  // Moment
  {
    from: 'node_modules/moment/min',
    to: 'jslib/moment',
    filterName: '.min.',
    excludeName: '.map'
  },
  {
    from: 'node_modules/moment/locale',
    to: 'jslib/moment/locale'
  },
  // Date Range Picker
  {
    from: 'node_modules/daterangepicker/',
    to: 'jslib/daterangepicker',
    filterName: 'daterangepicker.'
  },
  // DataTables
  {
    from: 'node_modules/pdfmake/build',
    to: 'jslib/pdfmake',
    excludeName: ['pdfmake.js', '.map']
  },
  {
    from: 'node_modules/jszip/dist',
    to: 'jslib/jszip',
    filterName: '.min'
  },
  {
    from: 'node_modules/datatables.net/js',
    to: 'jslib/datatables',
    filterName: '.min'
  },
  {
    from: 'node_modules/datatables.net-bs5/js',
    to: 'jslib/datatables-bs5/js',
    filterName: '.min',
    excludeName: ['.map', '.mjs']
  },
  {
    from: 'node_modules/datatables.net-bs5/css',
    to: 'jslib/datatables-bs5/css',
    filterName: '.min'
  },
  {
    from: 'node_modules/datatables.net-buttons-bs5/js',
    to: 'jslib/datatables-buttons-bs5/js',
    filterName: '.min.js'
  },
  {
    from: 'node_modules/datatables.net-buttons/js',
    to: 'jslib/datatables-buttons/js',
    filterName: '.min',
    excludeName: '.mjs'
  },
  {
    from: 'node_modules/datatables.net-buttons-bs5/css',
    to: 'jslib/datatables-buttons/css',
    filterName: '.min'
  },

  // inputmask
  {
    from: 'node_modules/inputmask/dist/',
    to: 'jslib/inputmask',
    filterName: '.min'
  },
  // Select2
  {
    from: 'node_modules/select2/dist/',
    to: 'jslib/select2',
    filterName: '.min'
  },
  // Select2 (Bootstrap 5 theme)
  {
    from: 'node_modules/select2-bootstrap-5-theme/dist/',
    to: 'jslib/select2-bootstrap-5-theme',
    filterName: '.min'
  },
  // Toastr
  {
    from: 'node_modules/toastr/build/',
    to: 'jslib/toastr',
    filterName: '.min'
  },
  // bootstrap4-duallistbox
  {
    from: 'node_modules/bootstrap4-duallistbox/dist',
    to: 'jslib/bootstrap4-duallistbox/',
    filterName: '.min'
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
    to: 'jslib/floatthead',
    filterName: '.min'
  },
]

module.exports = jslib
