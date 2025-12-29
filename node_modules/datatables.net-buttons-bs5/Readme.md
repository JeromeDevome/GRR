# Buttons for DataTables with styling for [Bootstrap5](https://getbootstrap.com/)

This is the distribution package for the [Buttons extension](https://datatables.net/extensions/buttons) for [DataTables](https://datatables.net/) with styling for [Bootstrap5](https://getbootstrap.com/).

The Buttons extension for DataTables provides a common set of options, API methods and styling to display buttons on a page that will interact with a DataTable. It also provides plug-ins for file export (HTML5 and Flash), print view and column visibility. Other libraries, such as Editor and Select also provide buttons specific to their use cases.


## Installation

### Browser

To use DataTables with a simple `<script>` tag, rather than using this package, it is recommended that you use the [DataTables download builder](//datatables.net/download) which can create CDN or locally hosted packages for you, will all dependencies satisfied.

### npm

For installation via npm, yarn and other similar package managers, install this package with your package manager - e.g.:

```
npm install datatables.net-bs5
npm install datatables.net-buttons-bs5
```

Then, to load and initialise the software in your code use:

```
import DataTable from 'datatables.net-bs5';
import 'datatables.net-buttons-bs5'

new DataTable('#myTable', {
    // initialisation options
});
```


## Documentation

Full documentation and examples for Buttons can be found [on the DataTables website](https://datatables.net/extensions/buttons).


## Bug / Support

Support for DataTables is available through the [DataTables forums](//datatables.net/forums) and [commercial support options](//datatables.net/support) are available.

### Contributing

If you are thinking of contributing code to DataTables, first of all, thank you! All fixes, patches and enhancements to DataTables are very warmly welcomed. This repository is a distribution repo, so patches and issues sent to this repo will not be accepted. Instead, please direct pull requests to the [DataTables/Buttons](http://github.com/DataTables/Buttons). For issues / bugs, please direct your questions to the [DataTables forums](//datatables.net/forums).


## License

This software is released under the [MIT license](//datatables.net/license). You are free to use, modify and distribute this software, but all copyright information must remain.

