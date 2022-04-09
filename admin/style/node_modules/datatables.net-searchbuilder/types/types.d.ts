/// <reference types="jquery" />
/// <reference types="datatables.net"/>

import {IDefaults, IDetails} from './searchBuilder';

declare namespace DataTables {

	interface Settings {
		searchBuilder?: boolean | string[] | IDefaults | IDefaults[];
	}

	interface LanguageSettings {
		searchBuilder?: {}
	}

	interface Api<T> {
		searchBuilder: SearchBuilderGlobalApi;
	}

	interface SearchBuilderGlobalApi {
		/**
		 * Returns the node of the SearchBuilder Container
		 */
		container(): JQuery<HTMLElement>;

		/**
		 * Gets the details of the current SearchBuilder setup
		 */
		getDetails(): IDetails;

		/**
		 * Rebuild the search to a given state.
		 *
		 * @param state Object of the same structue that is returned from searchBuilder.getDetails().
		 * This contains all of the details needed to rebuild the state.
		 * @returns self for chaining
		 */
		rebuild(state: IDetails): Api<any>;
	}
}
