/// <reference types="jquery" />
/// <reference types="datatables.net"/>


import FixedColumns, * as fixedColumnType from './FixedColumns';

declare namespace DataTables {

	interface Settings {
		/**
		 * FixedColumns extension options
		 */
		fixedColumns?: boolean | string[] | fixedColumnType.IDefaults | fixedColumnType.IDefaults[];
	}

	interface LanguageSettings {
		fixedColumns?: {};
	}

	interface Api<T> {
		/**
		 * FixedColumns API Methods
		 */
		fixedColumns(): FixedColumnsGlobalApi;
	}

	interface FixedColumnsGlobalApi {

		/**
		 * Getter/Setter for the number of fixed columns to the left of the table
		 *
		 * @param newVal Optional. If a value is supplied this is used to set the number of columns
		 * that are fixed to the left of the table. If no value is provided then the current number is returned
		 * @returns DataTables Api for chaining if setting, number if getting
		 */
		left(newVal?: number): Api<any> | number;

		/**
		 * Getter/Setter for the number of fixed columns to the right of the table
		 *
		 * @param newVal Optional. If a value is supplied this is used to set the number of columns
		 * that are fixed to the right of the table. If no value is provided then the current number is returned
		 * @returns DataTables Api for chaining if setting, number if getting
		 */
		right(newVal?: number): Api<any> | number;
	}
}
