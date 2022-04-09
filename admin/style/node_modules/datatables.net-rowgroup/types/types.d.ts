// Type definitions for datatables.net-rowgroup 1.0
// Project: https://datatables.net/extensions/rowgroup/, https://datatables.net
// Definitions by: Matthieu Tabuteau <https://github.com/maixiu>
// Definitions: https://github.com/DefinitelyTyped/DefinitelyTyped
// TypeScript Version: 2.4

/// <reference types="jquery" />
/// <reference types="datatables.net"/>

declare namespace DataTables {
    interface Settings {
        /**
         * RowGroup extension options
         */
        rowGroup?: boolean | RowGroupSettings;
    }

    interface StaticFunctions {
        RowGroup: RowGroupStaticFunctions;
    }

    interface RowGroupStaticFunctions {
        new (dt: Api<any>, settings: boolean | RowGroupSettings): undefined;
        version: string;
        defaults: RowGroupSettings;
    }

    interface Api<T> {
        rowGroup(): RowGroupApi;
    }

    interface RowGroupApi {
        /**
         * Get the data source for the row grouping
         * 
         * @returns Data source property
         */
        dataSrc(): number | string;

        /**
         * Set the data source for the row grouping
         * 
         * @param prop Data source property
         * @returns DataTables Api instance
         */
        dataSrc(prop: number|string): Api<any>;

        /**
         * Disable RowGroup's interaction with the table
         * 
         * @returns DataTables API instance
         */
        disable(): Api<any>;

        /**
         * Enable or disable RowGroup's interaction with the table
         * 
         * @param enable Either enables or disables RowGroup depending on the value of the flag
         * @returns DataTables Api instance
         */
        enable(enable?: boolean): Api<any>;

        /**
         * Get the enabled state for RowGroup.
         * 
         * @returns true if enabled, false otherwise
         */
        enabled(): boolean;
    }

    /**
     * RowGroup extension options
     */
    interface RowGroupSettings {
        /**
         * Set the class name to be used for the grouping rows
         */
        className?: string;

        /**
         * Set the data point to use as the grouping data source
         */
        dataSrc?: number|string;

        /**
         * Provides the ability to disable row grouping at initialisation
         */
        enable?: boolean;

        /**
         * Set the class name to be used for the grouping end rows
         */
        endClassName?: string;

        /**
         * Provide a function that can be used to control the data shown in the end grouping row
         */
        endRender?: (rows: Api<any>, group: string) => string|HTMLElement|JQuery;

        /**
         * Provide a function that can be used to control the data shown in the start grouping row
         */
        startRender?: (rows: Api<any>, group: string) => string|HTMLElement|JQuery;
    }
}
