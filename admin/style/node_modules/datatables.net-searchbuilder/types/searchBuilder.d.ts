/// <reference types="jquery" />
/// <reference types="datatables.net" />
/**
 * Sets the value of jQuery for use in the file
 *
 * @param jq the instance of jQuery to be set
 */
export declare function setJQuery(jq: any): void;
import * as criteriaType from './criteria';
import Group from './group';
export interface IDetails {
    criteria: Group[];
    logic: string;
}
export interface IClasses {
    button: string;
    clearAll: string;
    container: string;
    inputButton: string;
    title: string;
    titleRow: string;
}
export interface IDefaults {
    columns: number[] | boolean;
    conditions: {
        [keys: string]: {
            [keys: string]: criteriaType.ICondition;
        };
    };
    depthLimit: boolean | number;
    enterSearch: boolean;
    filterChanged: (count: number, text: string) => void;
    greyscale: boolean;
    i18n: II18n;
    logic: string;
    orthogonal: criteriaType.IOrthogonal;
    preDefined: boolean | IDetails;
}
export interface IDom {
    clearAll: JQuery<HTMLElement>;
    container: JQuery<HTMLElement>;
    title: JQuery<HTMLElement>;
    titleRow: JQuery<HTMLElement>;
    topGroup: JQuery<HTMLElement>;
}
export interface II18n {
    add: string;
    button: {
        0: string;
        _: string;
    };
    clearAll: string;
    condition: string;
    conditions?: {
        [s: string]: {
            [t: string]: string;
        };
    };
    data: string;
    delete: string;
    deleteTitle: string;
    left: string;
    leftTitle: string;
    logicAnd: string;
    logicOr: string;
    right: string;
    rightTitle: string;
    title: {
        0: string;
        _: string;
    };
    value: string;
    valueJoiner: string;
}
export interface IS {
    dt: any;
    opts: IDefaults;
    search: (settings: any, searchData: any, dataIndex: any, origData: any) => boolean;
    topGroup: Group;
}
/**
 * SearchBuilder class for DataTables.
 * Allows for complex search queries to be constructed and implemented on a DataTable
 */
export default class SearchBuilder {
    private static version;
    private static classes;
    private static defaults;
    classes: IClasses;
    dom: IDom;
    c: IDefaults;
    s: IS;
    constructor(builderSettings: any, opts: IDefaults);
    /**
     * Gets the details required to rebuild the SearchBuilder as it currently is
     */
    getDetails(deFormatDates?: boolean): IDetails | {};
    /**
     * Getter for the node of the container for the searchBuilder
     *
     * @returns JQuery<HTMLElement> the node of the container
     */
    getNode(): JQuery<HTMLElement>;
    /**
     * Rebuilds the SearchBuilder to a state that is provided
     *
     * @param details The details required to perform a rebuild
     */
    rebuild(details: any): SearchBuilder;
    /**
     * Applies the defaults to preDefined criteria
     *
     * @param preDef the array of criteria to be processed.
     */
    private _applyPreDefDefaults;
    /**
     * Set's up the SearchBuilder
     */
    private _setUp;
    private _collapseArray;
    /**
     * Updates the title of the SearchBuilder
     *
     * @param count the number of filters in the SearchBuilder
     */
    private _updateTitle;
    /**
     * Builds all of the dom elements together
     */
    private _build;
    /**
     * Checks if the clearAll button should be added or not
     */
    private _checkClear;
    /**
     * Update the count in the title/button
     *
     * @param count Number of filters applied
     */
    private _filterChanged;
    /**
     * Set the listener for the clear button
     */
    private _setClearListener;
    /**
     * Set the listener for the Redraw event
     */
    private _setRedrawListener;
    /**
     * Sets listeners to check whether clearAll should be added or removed
     */
    private _setEmptyListener;
}
