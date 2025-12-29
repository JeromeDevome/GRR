# DataTables with [Bootstrap5](https://getbootstrap.com/) styling

This package contains distribution files required to style [DataTables table enhancement library](https://datatables.net) with styling integration for [Bootstrap5](https://getbootstrap.com/).

DataTables is a table enhancing library which adds features such as paging, ordering, search, scrolling and many more to a static HTML page. A comprehensive API is also available that can be used to manipulate the table. Please refer to the [DataTables web-site](//datatables.net) for a full range of documentation and examples.


## Installation

### Browser

To use DataTables with a simple `<script>` tag, rather than using this package, it is recommended that you use the [DataTables download builder](//datatables.net/download) which can create CDN or locally hosted packages for you, will all dependencies satisfied.

### npm

For installation via npm, yarn and other similar package managers, install this package with your package manager - e.g.:

```
npm install datatables.net-bs5
```

Then, to load and initialise DataTables in your code use:

```
import DataTable from 'datatables.net-bs5';

new DataTable('#myTable', {
    // initialisation options
});
```

If you are using an old version of Node or a CommonJS loader, you might need to use the `require` syntax:

```
const DataTable = require('datatables.net-bs5');

new DataTable('#myTable', {
    // initialisation options
});
```


## Documentation

Full documentation of the DataTables options, API and plug-in interface are available on the [website](https://datatables.net/reference/index). The site also contains information on the wide variety of plug-ins that are available for DataTables, which can be used to enhance and customise your table even further.


## Bug / Support

Support for DataTables is available through the [DataTables forums](//datatables.net/forums) and [commercial support options](//datatables.net/support) are available.

### Contributing

If you are thinking of contributing code to DataTables, first of all, thank you! All fixes, patches and enhancements to DataTables are very warmly welcomed. This repository is a distribution repo, so patches and issues sent to this repo will not be accepted. Instead, please direct pull requests to the [DataTables/DataTablesSrc](http://github.com/DataTables/DataTablesSrc). For issues / bugs, please direct your questions to the [DataTables forums](//datatables.net/forums).


## License

This software is released under the [MIT license](//datatables.net/license). You are free to use, modify and distribute this software, but all copyright information must remain.

