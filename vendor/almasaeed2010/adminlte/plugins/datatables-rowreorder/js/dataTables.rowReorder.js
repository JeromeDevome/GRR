/*! RowReorder 1.5.0
 * © SpryMedia Ltd - datatables.net/license
 */

(function( factory ){
	if ( typeof define === 'function' && define.amd ) {
		// AMD
		define( ['jquery', 'datatables.net'], function ( $ ) {
			return factory( $, window, document );
		} );
	}
	else if ( typeof exports === 'object' ) {
		// CommonJS
		var jq = require('jquery');
		var cjsRequires = function (root, $) {
			if ( ! $.fn.dataTable ) {
				require('datatables.net')(root, $);
			}
		};

		if (typeof window === 'undefined') {
			module.exports = function (root, $) {
				if ( ! root ) {
					// CommonJS environments without a window global must pass a
					// root. This will give an error otherwise
					root = window;
				}

				if ( ! $ ) {
					$ = jq( root );
				}

				cjsRequires( root, $ );
				return factory( $, root, root.document );
			};
		}
		else {
			cjsRequires( window, jq );
			module.exports = factory( jq, window, window.document );
		}
	}
	else {
		// Browser
		factory( jQuery, window, document );
	}
}(function( $, window, document ) {
'use strict';
var DataTable = $.fn.dataTable;



/**
 * @summary     RowReorder
 * @description Row reordering extension for DataTables
 * @version     1.5.0
 * @author      SpryMedia Ltd
 * @contact     datatables.net
 *
 * This source file is free software, available under the following license:
 *   MIT license - http://datatables.net/license/mit
 *
 * This source file is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the license files for details.
 *
 * For details please refer to: http://www.datatables.net
 */

/**
 * RowReorder provides the ability in DataTables to click and drag rows to
 * reorder them. When a row is dropped the data for the rows effected will be
 * updated to reflect the change. Normally this data point should also be the
 * column being sorted upon in the DataTable but this does not need to be the
 * case. RowReorder implements a "data swap" method - so the rows being
 * reordered take the value of the data point from the row that used to occupy
 * the row's new position.
 *
 * Initialisation is done by either:
 *
 * * `rowReorder` parameter in the DataTable initialisation object
 * * `new DataTable.RowReorder( table, opts )` after DataTables
 *   initialisation.
 *
 *  @class
 *  @param {object} settings DataTables settings object for the host table
 *  @param {object} [opts] Configuration options
 *  @requires jQuery 1.7+
 *  @requires DataTables 1.11
 */
var RowReorder = function (dt, opts) {
	// Sanity check that we are using DataTables 1.10 or newer
	if (!DataTable.versionCheck || !DataTable.versionCheck('1.11')) {
		throw 'DataTables RowReorder requires DataTables 1.11 or newer';
	}

	// User and defaults configuration object
	this.c = $.extend(true, {}, DataTable.defaults.rowReorder, RowReorder.defaults, opts);

	// Internal settings
	this.s = {
		/** @type {integer} Scroll body top cache */
		bodyTop: null,

		/** @type {DataTable.Api} DataTables' API instance */
		dt: new DataTable.Api(dt),

		/** @type {function} Data fetch function */
		getDataFn: DataTable.util.get(this.c.dataSrc),

		/** @type {array} Pixel positions for row insertion calculation */
		middles: null,

		/** @type {Object} Cached dimension information for use in the mouse move event handler */
		scroll: {},

		/** @type {integer} Interval object used for smooth scrolling */
		scrollInterval: null,

		/** @type {function} Data set function */
		setDataFn: DataTable.util.set(this.c.dataSrc),

		/** @type {Object} Mouse down information */
		start: {
			top: 0,
			left: 0,
			offsetTop: 0,
			offsetLeft: 0,
			nodes: [],
			rowIndex: 0
		},

		/** @type {integer} Window height cached value */
		windowHeight: 0,

		/** @type {integer} Document outer height cached value */
		documentOuterHeight: 0,

		/** @type {integer} DOM clone outer height cached value */
		domCloneOuterHeight: 0,

		/** @type {integer} Flag used for signing if the drop is enabled or not */
		dropAllowed: true
	};

	// DOM items
	this.dom = {
		/** @type {jQuery} Cloned row being moved around */
		clone: null,
		cloneParent: null,

		/** @type {jQuery} DataTables scrolling container */
		dtScroll: $('div.dataTables_scrollBody, div.dt-scroll-body', this.s.dt.table().container())
	};

	// Check if row reorder has already been initialised on this table
	var settings = this.s.dt.settings()[0];
	var existing = settings.rowreorder;

	if (existing) {
		return existing;
	}

	if (!this.dom.dtScroll.length) {
		this.dom.dtScroll = $(this.s.dt.table().container(), 'tbody');
	}

	settings.rowreorder = this;
	this._constructor();
};

$.extend(RowReorder.prototype, {
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	/**
	 * Initialise the RowReorder instance
	 *
	 * @private
	 */
	_constructor: function () {
		var that = this;
		var dt = this.s.dt;
		var table = $(dt.table().node());

		// Need to be able to calculate the row positions relative to the table
		if (table.css('position') === 'static') {
			table.css('position', 'relative');
		}

		// listen for mouse down on the target column - we have to implement
		// this rather than using HTML5 drag and drop as drag and drop doesn't
		// appear to work on table rows at this time. Also mobile browsers are
		// not supported.
		// Use `table().container()` rather than just the table node for IE8 -
		// otherwise it only works once...
		$(dt.table().container()).on(
			'mousedown.rowReorder touchstart.rowReorder',
			this.c.selector,
			function (e) {
				if (!that.c.enable) {
					return;
				}

				// Ignore excluded children of the selector
				if ($(e.target).is(that.c.excludedChildren)) {
					return true;
				}

				var tr = $(this).closest('tr');
				var row = dt.row(tr);

				// Double check that it is a DataTable row
				if (row.any()) {
					that._emitEvent('pre-row-reorder', {
						node: row.node(),
						index: row.index()
					});

					that._mouseDown(e, tr);
					return false;
				}
			}
		);

		dt.on('destroy.rowReorder', function () {
			$(dt.table().container()).off('.rowReorder');
			dt.off('.rowReorder');
		});

		this._keyup = this._keyup.bind(this);
	},

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private methods
	 */

	/**
	 * Cache the measurements that RowReorder needs in the mouse move handler
	 * to attempt to speed things up, rather than reading from the DOM.
	 *
	 * @private
	 */
	_cachePositions: function () {
		var dt = this.s.dt;

		// Frustratingly, if we add `position:relative` to the tbody, the
		// position is still relatively to the parent. So we need to adjust
		// for that
		var headerHeight = $(dt.table().node()).find('thead').outerHeight();

		// Need to pass the nodes through jQuery to get them in document order,
		// not what DataTables thinks it is, since we have been altering the
		// order
		var nodes = $.unique(dt.rows({ page: 'current' }).nodes().toArray());
		var middles = $.map(nodes, function (node, i) {
			var top = $(node).position().top - headerHeight;

			return (top + top + $(node).outerHeight()) / 2;
		});

		this.s.middles = middles;
		this.s.bodyTop = $(dt.table().body()).offset().top;
		this.s.windowHeight = $(window).height();
		this.s.documentOuterHeight = $(document).outerHeight();
		this.s.bodyArea = this._calcBodyArea();
	},

	/**
	 * Clone a row so it can be floated around the screen
	 *
	 * @param  {jQuery} target Node to be cloned
	 * @private
	 */
	_clone: function (target) {
		var dt = this.s.dt;
		var clone = $(dt.table().node().cloneNode(false))
			.addClass('dt-rowReorder-float')
			.append('<tbody/>')
			.append(target.clone(false));

		// Match the table and column widths - read all sizes before setting
		// to reduce reflows
		var tableWidth = target.outerWidth();
		var tableHeight = target.outerHeight();
		var scrollBody = $($(this.s.dt.table().node()).parent());
		var scrollWidth = scrollBody.width();
		var scrollLeft = scrollBody.scrollLeft();
		var sizes = target.children().map(function () {
			return $(this).width();
		});

		clone
			.width(tableWidth)
			.height(tableHeight)
			.find('tr')
			.children()
			.each(function (i) {
				this.style.width = sizes[i] + 'px';
			});

		var cloneParent = $('<div>')
			.addClass('dt-rowReorder-float-parent')
			.width(scrollWidth)
			.append(clone)
			.appendTo('body')
			.scrollLeft(scrollLeft);

		// Insert into the document to have it floating around

		this.dom.clone = clone;
		this.dom.cloneParent = cloneParent;
		this.s.domCloneOuterHeight = clone.outerHeight();
	},

	/**
	 * Update the cloned item's position in the document
	 *
	 * @param  {object} e Event giving the mouse's position
	 * @private
	 */
	_clonePosition: function (e) {
		var start = this.s.start;
		var topDiff = this._eventToPage(e, 'Y') - start.top;
		var leftDiff = this._eventToPage(e, 'X') - start.left;
		var snap = this.c.snapX;
		var left;
		var top = topDiff + start.offsetTop;

		if (snap === true) {
			left = start.offsetLeft;
		}
		else if (typeof snap === 'number') {
			left = start.offsetLeft + snap;
		}
		else {
			left = leftDiff + start.offsetLeft + this.dom.cloneParent.scrollLeft();
		}

		if (top < 0) {
			top = 0;
		}
		else if (top + this.s.domCloneOuterHeight > this.s.documentOuterHeight) {
			top = this.s.documentOuterHeight - this.s.domCloneOuterHeight;
		}

		this.dom.cloneParent.css({
			top: top,
			left: left
		});
	},

	/**
	 * Emit an event on the DataTable for listeners
	 *
	 * @param  {string} name Event name
	 * @param  {array} args Event arguments
	 * @private
	 */
	_emitEvent: function ( name, args )
	{
		var ret;

		this.s.dt.iterator( 'table', function ( ctx, i ) {
			var innerRet = $(ctx.nTable).triggerHandler( name+'.dt', args );

			if (innerRet !== undefined) {
				ret = innerRet;
			}
		} );

		return ret;
	},

	/**
	 * Get pageX/Y position from an event, regardless of if it is a mouse or
	 * touch event.
	 *
	 * @param  {object} e Event
	 * @param  {string} pos X or Y (must be a capital)
	 * @private
	 */
	_eventToPage: function (e, pos) {
		if (e.type.indexOf('touch') !== -1) {
			return e.originalEvent.touches[0]['page' + pos];
		}

		return e['page' + pos];
	},

	/**
	 * Mouse down event handler. Read initial positions and add event handlers
	 * for the move.
	 *
	 * @param  {object} e      Mouse event
	 * @param  {jQuery} target TR element that is to be moved
	 * @private
	 */
	_mouseDown: function (e, target) {
		var that = this;
		var dt = this.s.dt;
		var start = this.s.start;
		var cancelable = this.c.cancelable;

		var offset = target.offset();
		start.top = this._eventToPage(e, 'Y');
		start.left = this._eventToPage(e, 'X');
		start.offsetTop = offset.top;
		start.offsetLeft = offset.left;
		start.nodes = $.unique(dt.rows({ page: 'current' }).nodes().toArray());

		this._cachePositions();
		this._clone(target);
		this._clonePosition(e);

		var bodyY = this._eventToPage(e, 'Y') - this.s.bodyTop;
		start.rowIndex = this._calcRowIndexByPos(bodyY);

		this.dom.target = target;
		target.addClass('dt-rowReorder-moving');

		$(document)
			.on('mouseup.rowReorder touchend.rowReorder', function (e) {
				that._mouseUp(e);
			})
			.on('mousemove.rowReorder touchmove.rowReorder', function (e) {
				that._mouseMove(e);
			});

		// Check if window is x-scrolling - if not, disable it for the duration
		// of the drag
		if ($(window).width() === $(document).width()) {
			$(document.body).addClass('dt-rowReorder-noOverflow');
		}

		// Cache scrolling information so mouse move doesn't need to read.
		// This assumes that the window and DT scroller will not change size
		// during an row drag, which I think is a fair assumption
		var scrollWrapper = this.dom.dtScroll;
		this.s.scroll = {
			windowHeight: $(window).height(),
			windowWidth: $(window).width(),
			dtTop: scrollWrapper.length ? scrollWrapper.offset().top : null,
			dtLeft: scrollWrapper.length ? scrollWrapper.offset().left : null,
			dtHeight: scrollWrapper.length ? scrollWrapper.outerHeight() : null,
			dtWidth: scrollWrapper.length ? scrollWrapper.outerWidth() : null
		};

		// Add keyup handler if dragging is cancelable
		if (cancelable) {
			$(document).on('keyup', this._keyup);
		}
	},

	/**
	 * Mouse move event handler - move the cloned row and shuffle the table's
	 * rows if required.
	 *
	 * @param  {object} e Mouse event
	 * @private
	 */
	_mouseMove: function (e) {
		this._clonePosition(e);

		var start = this.s.start;
		var cancelable = this.c.cancelable;

		if (cancelable) {
			var bodyArea = this.s.bodyArea;
			var cloneArea = this._calcCloneParentArea();
			this.s.dropAllowed = this._rectanglesIntersect(bodyArea, cloneArea);

			this.s.dropAllowed
				? $(this.dom.cloneParent).removeClass('drop-not-allowed')
				: $(this.dom.cloneParent).addClass('drop-not-allowed');
		}

		// Transform the mouse position into a position in the table's body
		var bodyY = this._eventToPage(e, 'Y') - this.s.bodyTop;
		var middles = this.s.middles;
		var insertPoint = null;

		// Determine where the row should be inserted based on the mouse
		// position
		for (var i = 0, ien = middles.length; i < ien; i++) {
			if (bodyY < middles[i]) {
				insertPoint = i;
				break;
			}
		}

		if (insertPoint === null) {
			insertPoint = middles.length;
		}

		if (cancelable) {
			if (!this.s.dropAllowed) {
				// Move the row back to its original position becasuse the drop is not allowed
				insertPoint =
					start.rowIndex > this.s.lastInsert ? start.rowIndex + 1 : start.rowIndex;
			}

			this.dom.target.toggleClass('dt-rowReorder-moving', this.s.dropAllowed);
		}

		this._moveTargetIntoPosition(insertPoint);

		this._shiftScroll(e);
	},

	/**
	 * Mouse up event handler - release the event handlers and perform the
	 * table updates
	 *
	 * @param  {object} e Mouse event
	 * @private
	 */
	_mouseUp: function (e) {
		var that = this;
		var dt = this.s.dt;
		var i, ien;
		var dataSrc = this.c.dataSrc;
		var dropAllowed = this.s.dropAllowed;

		if (!dropAllowed) {
			that._cancel();
			return;
		}

		// Calculate the difference
		var startNodes = this.s.start.nodes;
		var endNodes = $.unique(dt.rows({ page: 'current' }).nodes().toArray());
		var idDiff = {};
		var fullDiff = [];
		var diffNodes = [];
		var getDataFn = this.s.getDataFn;
		var setDataFn = this.s.setDataFn;

		for (i = 0, ien = startNodes.length; i < ien; i++) {
			if (startNodes[i] !== endNodes[i]) {
				var id = dt.row(endNodes[i]).id();
				var endRowData = dt.row(endNodes[i]).data();
				var startRowData = dt.row(startNodes[i]).data();

				if (id) {
					idDiff[id] = getDataFn(startRowData);
				}

				fullDiff.push({
					node: endNodes[i],
					oldData: getDataFn(endRowData),
					newData: getDataFn(startRowData),
					newPosition: i,
					oldPosition: $.inArray(endNodes[i], startNodes)
				});

				diffNodes.push(endNodes[i]);
			}
		}

		// Create event args
		var eventArgs = [
			fullDiff,
			{
				dataSrc: dataSrc,
				nodes: diffNodes,
				values: idDiff,
				triggerRow: dt.row(this.dom.target),
				originalEvent: e
			}
		];

		// Emit event
		var eventResult = this._emitEvent( 'row-reorder', eventArgs );

		if (eventResult === false) {
			that._cancel();
			return;
		}

		// Remove cloned elements, handlers, etc
		this._cleanupDragging();

		var update = function () {
			if (that.c.update) {
				for (i = 0, ien = fullDiff.length; i < ien; i++) {
					var row = dt.row(fullDiff[i].node);
					var rowData = row.data();

					setDataFn(rowData, fullDiff[i].newData);

					// Invalidate the cell that has the same data source as the dataSrc
					dt.columns().every(function () {
						if (this.dataSrc() === dataSrc) {
							dt.cell(fullDiff[i].node, this.index()).invalidate('data');
						}
					});
				}

				// Trigger row reordered event
				that._emitEvent('row-reordered', eventArgs);

				dt.draw(false);
			}
		};

		// Editor interface
		if (this.c.editor) {
			// Disable user interaction while Editor is submitting
			this.c.enable = false;

			this.c.editor
				.edit(diffNodes, false, $.extend({ submit: 'changed' }, this.c.formOptions))
				.multiSet(dataSrc, idDiff)
				.one('preSubmitCancelled.rowReorder', function () {
					that.c.enable = true;
					that.c.editor.off('.rowReorder');
					dt.draw(false);
				})
				.one('submitUnsuccessful.rowReorder', function () {
					dt.draw(false);
				})
				.one('submitSuccess.rowReorder', function () {
					update();
				})
				.one('submitComplete', function () {
					that.c.enable = true;
					that.c.editor.off('.rowReorder');
				})
				.submit();
		}
		else {
			update();
		}
	},

	/**
	 * Moves the current target into the given position within the table
	 * and caches the new positions
	 *
	 * @param  {integer} insertPoint Position
	 * @private
	 */
	_moveTargetIntoPosition: function (insertPoint) {
		var dt = this.s.dt;

		// Perform the DOM shuffle if it has changed from last time
		if (this.s.lastInsert === null || this.s.lastInsert !== insertPoint) {
			var nodes = $.unique(dt.rows({ page: 'current' }).nodes().toArray());
			var insertPlacement = '';

			if (insertPoint > this.s.lastInsert) {
				this.dom.target.insertAfter(nodes[insertPoint - 1]);
				insertPlacement = 'after';
			}
			else {
				this.dom.target.insertBefore(nodes[insertPoint]);
				insertPlacement = 'before';
			}

			this._cachePositions();

			this.s.lastInsert = insertPoint;

			this._emitEvent('row-reorder-changed', {
				insertPlacement,
				insertPoint,
				row: dt.row(this.dom.target)
			});
		}
	},

	/**
	 * Removes the cloned elements, event handlers, scrolling intervals, etc
	 *
	 * @private
	 */
	_cleanupDragging: function () {
		var cancelable = this.c.cancelable;

		this.dom.clone.remove();
		this.dom.cloneParent.remove();
		this.dom.clone = null;
		this.dom.cloneParent = null;

		this.dom.target.removeClass('dt-rowReorder-moving');
		//this.dom.target = null;

		$(document).off('.rowReorder');
		$(document.body).removeClass('dt-rowReorder-noOverflow');

		clearInterval(this.s.scrollInterval);
		this.s.scrollInterval = null;

		if (cancelable) {
			$(document).off('keyup', this._keyup);
		}
	},

	/**
	 * Move the window and DataTables scrolling during a drag to scroll new
	 * content into view.
	 *
	 * This matches the `_shiftScroll` method used in AutoFill, but only
	 * horizontal scrolling is considered here.
	 *
	 * @param  {object} e Mouse move event object
	 * @private
	 */
	_shiftScroll: function (e) {
		var that = this;
		var scroll = this.s.scroll;
		var runInterval = false;
		var scrollSpeed = 5;
		var buffer = 65;
		var windowY = e.pageY - document.body.scrollTop,
			windowVert,
			dtVert;

		// Window calculations - based on the mouse position in the window,
		// regardless of scrolling
		if (windowY < $(window).scrollTop() + buffer) {
			windowVert = scrollSpeed * -1;
		}
		else if (windowY > scroll.windowHeight + $(window).scrollTop() - buffer) {
			windowVert = scrollSpeed;
		}

		// DataTables scrolling calculations - based on the table's position in
		// the document and the mouse position on the page
		if (scroll.dtTop !== null && e.pageY < scroll.dtTop + buffer) {
			dtVert = scrollSpeed * -1;
		}
		else if (scroll.dtTop !== null && e.pageY > scroll.dtTop + scroll.dtHeight - buffer) {
			dtVert = scrollSpeed;
		}

		// This is where it gets interesting. We want to continue scrolling
		// without requiring a mouse move, so we need an interval to be
		// triggered. The interval should continue until it is no longer needed,
		// but it must also use the latest scroll commands (for example consider
		// that the mouse might move from scrolling up to scrolling left, all
		// with the same interval running. We use the `scroll` object to "pass"
		// this information to the interval. Can't use local variables as they
		// wouldn't be the ones that are used by an already existing interval!
		if (windowVert || dtVert) {
			scroll.windowVert = windowVert;
			scroll.dtVert = dtVert;
			runInterval = true;
		}
		else if (this.s.scrollInterval) {
			// Don't need to scroll - remove any existing timer
			clearInterval(this.s.scrollInterval);
			this.s.scrollInterval = null;
		}

		// If we need to run the interval to scroll and there is no existing
		// interval (if there is an existing one, it will continue to run)
		if (!this.s.scrollInterval && runInterval) {
			this.s.scrollInterval = setInterval(function () {
				// Don't need to worry about setting scroll <0 or beyond the
				// scroll bound as the browser will just reject that.
				if (scroll.windowVert) {
					var top = $(document).scrollTop();
					$(document).scrollTop(top + scroll.windowVert);

					if (top !== $(document).scrollTop()) {
						var move = parseFloat(that.dom.cloneParent.css('top'));
						that.dom.cloneParent.css('top', move + scroll.windowVert);
					}
				}

				// DataTables scrolling
				if (scroll.dtVert) {
					var scroller = that.dom.dtScroll[0];

					if (scroll.dtVert) {
						scroller.scrollTop += scroll.dtVert;
					}
				}
			}, 20);
		}
	},

	/**
	 * Calculates the current area of the table body and returns it as a rectangle
	 *
	 * @private
	 */
	_calcBodyArea: function (e) {
		var dt = this.s.dt;
		var offset = $(dt.table().body()).offset();
		var area = {
			left: offset.left,
			top: offset.top,
			right: offset.left + $(dt.table().body()).width(),
			bottom: offset.top + $(dt.table().body()).height()
		};

		return area;
	},

	/**
	 * Calculates the current area of the cloned parent element and returns it as a rectangle
	 *
	 * @private
	 */
	_calcCloneParentArea: function (e) {
		var offset = $(this.dom.cloneParent).offset();
		var area = {
			left: offset.left,
			top: offset.top,
			right: offset.left + $(this.dom.cloneParent).width(),
			bottom: offset.top + $(this.dom.cloneParent).height()
		};

		return area;
	},

	/**
	 * Returns whether the given reactangles intersect or not
	 *
	 * @private
	 */
	_rectanglesIntersect: function (a, b) {
		var noOverlap =
			a.left >= b.right || b.left >= a.right || a.top >= b.bottom || b.top >= a.bottom;

		return !noOverlap;
	},

	/**
	 * Calculates the index of the row which lays under the given Y position or
	 * returns -1 if no such row
	 *
	 * @param  {integer} insertPoint Position
	 * @private
	 */
	_calcRowIndexByPos: function (bodyY) {
		// Determine where the row is located based on the mouse
		// position

		var dt = this.s.dt;
		var nodes = $.unique(dt.rows({ page: 'current' }).nodes().toArray());
		var rowIndex = -1;
		var headerHeight = $(dt.table().node()).find('thead').outerHeight();

		$.each(nodes, function (i, node) {
			var top = $(node).position().top - headerHeight;
			var bottom = top + $(node).outerHeight();

			if (bodyY >= top && bodyY <= bottom) {
				rowIndex = i;
			}
		});

		return rowIndex;
	},

	/**
	 * Handles key up events and cancels the dragging if ESC key is pressed
	 *
	 * @param  {object} e Mouse move event object
	 * @private
	 */
	_keyup: function (e) {
		var cancelable = this.c.cancelable;

		if (cancelable && e.which === 27) {
			// ESC key is up
			e.preventDefault();
			this._cancel();
		}
	},

	/**
	 * Cancels the dragging, moves target back into its original position
	 * and cleans up the dragging
	 *
	 * @param  {object} e Mouse move event object
	 * @private
	 */
	_cancel: function () {
		var start = this.s.start;
		var insertPoint = start.rowIndex > this.s.lastInsert ? start.rowIndex + 1 : start.rowIndex;

		this._moveTargetIntoPosition(insertPoint);

		this._cleanupDragging();

		// Emit event
		this._emitEvent('row-reorder-canceled', [this.s.start.rowIndex]);
	}
});

/**
 * RowReorder default settings for initialisation
 *
 * @namespace
 * @name RowReorder.defaults
 * @static
 */
RowReorder.defaults = {
	/**
	 * Data point in the host row's data source object for where to get and set
	 * the data to reorder. This will normally also be the sorting column.
	 *
	 * @type {Number}
	 */
	dataSrc: 0,

	/**
	 * Editor instance that will be used to perform the update
	 *
	 * @type {DataTable.Editor}
	 */
	editor: null,

	/**
	 * Enable / disable RowReorder's user interaction
	 * @type {Boolean}
	 */
	enable: true,

	/**
	 * Form options to pass to Editor when submitting a change in the row order.
	 * See the Editor `from-options` object for details of the options
	 * available.
	 * @type {Object}
	 */
	formOptions: {},

	/**
	 * Drag handle selector. This defines the element that when dragged will
	 * reorder a row.
	 *
	 * @type {String}
	 */
	selector: 'td:first-child',

	/**
	 * Optionally lock the dragged row's x-position. This can be `true` to
	 * fix the position match the host table's, `false` to allow free movement
	 * of the row, or a number to define an offset from the host table.
	 *
	 * @type {Boolean|number}
	 */
	snapX: false,

	/**
	 * Update the table's data on drop
	 *
	 * @type {Boolean}
	 */
	update: true,

	/**
	 * Selector for children of the drag handle selector that mouseDown events
	 * will be passed through to and drag will not activate
	 *
	 * @type {String}
	 */
	excludedChildren: 'a',

	/**
	 * Enable / disable the canceling of the drag & drop interaction
	 *
	 * @type {Boolean}
	 */
	cancelable: false
};

/*
 * API
 */
var Api = $.fn.dataTable.Api;

// Doesn't do anything - work around for a bug in DT... Not documented
Api.register('rowReorder()', function () {
	return this;
});

Api.register('rowReorder.enable()', function (toggle) {
	if (toggle === undefined) {
		toggle = true;
	}

	return this.iterator('table', function (ctx) {
		if (ctx.rowreorder) {
			ctx.rowreorder.c.enable = toggle;
		}
	});
});

Api.register('rowReorder.disable()', function () {
	return this.iterator('table', function (ctx) {
		if (ctx.rowreorder) {
			ctx.rowreorder.c.enable = false;
		}
	});
});

/**
 * Version information
 *
 * @name RowReorder.version
 * @static
 */
RowReorder.version = '1.5.0';

$.fn.dataTable.RowReorder = RowReorder;
$.fn.DataTable.RowReorder = RowReorder;

// Attach a listener to the document which listens for DataTables initialisation
// events so we can automatically initialise
$(document).on('init.dt.dtr', function (e, settings, json) {
	if (e.namespace !== 'dt') {
		return;
	}

	var init = settings.oInit.rowReorder;
	var defaults = DataTable.defaults.rowReorder;

	if (init || defaults) {
		var opts = $.extend({}, init, defaults);

		if (init !== false) {
			new RowReorder(settings, opts);
		}
	}
});


return DataTable;
}));
