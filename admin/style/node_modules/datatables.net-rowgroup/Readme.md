# RowGroup for DataTables 

This package contains distribution files for the [RowGroup extension](https://datatables.net/extensions/rowgroup) for [DataTables](https://datatables.net/). Only the core software for this library is contained in this package - to be correctly styled, a styling package for RowGroup must also be included. Styling options include DataTable's native styling, [Bootstrap](http://getbootstrap.com) and [Foundation](http://foundation.zurb.com/).

RowGroup adds the ability to easily group rows in a DataTable by a given data point. The grouping is shown as an inserted row either before or after the group.


## Installation

### Browser

For inclusion of this library using a standard `<script>` tag, rather than using this package, it is recommended that you use the [DataTables download builder](//datatables.net/download) which can create CDN or locally hosted packages for you, will all dependencies satisfied.

### npm

```
npm install datatables.net-rowgroup
```

ES3 Syntax
```
var $ = require( 'jquery' );
require( 'datatables.net-rowgroup' )( window, $ );
```

ES6 Syntax
```
import 'datatables.net-rowgroup'
```

### bower

```
bower install --save datatables.net-rowgroup
```



## Documentation

Full documentation and examples for RowGroup can be found [on the website](https://datatables.net/extensions/rowgroup).

## Bug / Support

Support for DataTables is available through the [DataTables forums](//datatables.net/forums) and [commercial support options](//datatables.net/support) are available.


### Contributing

If you are thinking of contributing code to DataTables, first of all, thank you! All fixes, patches and enhancements to DataTables are very warmly welcomed. This repository is a distribution repo, so patches and issues sent to this repo will not be accepted. Instead, please direct pull requests to the [DataTables/RowGroup](http://github.com/DataTables/RowGroup). For issues / bugs, please direct your questions to the [DataTables forums](//datatables.net/forums).


## License

This software is released under the [MIT license](//datatables.net/license). You are free to use, modify and distribute this software, but all copyright information must remain.
