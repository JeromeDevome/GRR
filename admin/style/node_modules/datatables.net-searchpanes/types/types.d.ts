/// <reference types="jquery" />
/// <reference types="datatables.net"/>


import * as paneType from './paneType';

declare namespace DataTables {

	interface Settings {
		/**
		 * SearchPanes extension options
		 */
		searchPanes?: boolean | string[] | paneType.IDefaults | paneType.IDefaults[];
	}

	interface LanguageSettings {
		searchPanes?: {};
	}

	interface Api<T> {
		/**
		 * SearchPanes API Methods
		 */
		searchPanes: SearchPanesGlobalApi;
	}

	interface SearchPanesGlobalApi {

		/**
		 * Clears the selections in all of the panes
		 *
		 * @returns self for chaining
		 */
		clearSelections(): Api<any>;

		/**
		 * Returns the node of the SearchPanes container
		 *
		 * @returns The node of the SearchPanes container
		 */
		container(): JQuery<HTMLElement>;

		/**
		 * Rebuilds the SearchPanes, regathering the options from the table.
		 *
		 * @param index Optional. The index of a specific pane to rebuild
		 * @param maintainSelect  Optional. Whether to remake the selections once the pane has been rebuilt.
		 * @returns self for chaining
		 */
		rebuildPane(index?: number, maintainSelect?: boolean): Api<any>;

		/**
		 * Resize all of the SearchPanes to fill the container appropriately.
		 *
		 * @returns self for chaining
		 */
		resizePanes(): Api<any>;
	}
}
