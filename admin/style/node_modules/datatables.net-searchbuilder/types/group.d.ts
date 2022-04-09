/// <reference types="jquery" />
/// <reference types="datatables.net" />
import Criteria, * as criteriaType from './criteria';
import * as builderType from './searchBuilder';
export interface IClassses {
    add: string;
    button: string;
    clearGroup: string;
    greyscale: string;
    group: string;
    inputButton: string;
    logic: string;
    logicContainer: string;
}
export interface IDom {
    add: JQuery<HTMLElement>;
    clear: JQuery<HTMLElement>;
    container: JQuery<HTMLElement>;
    logic: JQuery<HTMLElement>;
    logicContainer: JQuery<HTMLElement>;
}
export interface IS {
    criteria: ISCriteria[];
    depth: number;
    dt: any;
    index: number;
    isChild: boolean;
    logic: string;
    opts: builderType.IDefaults;
    preventRedraw: boolean;
    toDrop: Criteria;
    topGroup: JQuery<HTMLElement>;
}
export interface ICriteria {
    criteria: Array<IDetails | criteriaType.IDetails>;
    index: number;
}
export interface ICriteriaDetails {
    condition?: string;
    data?: string;
    logic?: string;
    value?: string[];
}
export interface ISCriteria {
    criteria: Group | Criteria;
    index: number;
    logic?: string;
}
export interface IDetails {
    criteria?: ICriteriaDetails[];
    index?: number;
    logic?: string;
}
/**
 * Sets the value of jQuery for use in the file
 *
 * @param jq the instance of jQuery to be set
 */
export declare function setJQuery(jq: any): void;
/**
 * The Group class is used within SearchBuilder to represent a group of criteria
 */
export default class Group {
    private static version;
    private static classes;
    private static defaults;
    classes: IClassses;
    dom: IDom;
    c: builderType.IDefaults;
    s: IS;
    constructor(table: any, opts: builderType.IDefaults, topGroup: JQuery<HTMLElement>, index?: number, isChild?: boolean, depth?: number);
    /**
     * Destroys the groups buttons, clears the internal criteria and removes it from the dom
     */
    destroy(): void;
    /**
     * Gets the details required to rebuild the group
     */
    getDetails(deFormatDates?: boolean): IDetails | {};
    /**
     * Getter for the node for the container of the group
     *
     * @returns Node for the container of the group
     */
    getNode(): JQuery<HTMLElement>;
    /**
     * Rebuilds the group based upon the details passed in
     *
     * @param loadedDetails the details required to rebuild the group
     */
    rebuild(loadedDetails: IDetails | criteriaType.IDetails): void;
    /**
     * Redraws the Contents of the searchBuilder Groups and Criteria
     */
    redrawContents(): void;
    /**
     * Resizes the logic button only rather than the entire dom.
     */
    redrawLogic(): void;
    /**
     * Search method, checking the row data against the criteria in the group
     *
     * @param rowData The row data to be compared
     * @returns boolean The result of the search
     */
    search(rowData: any[], rowIdx: number): boolean;
    /**
     * Locates the groups logic button to the correct location on the page
     */
    setupLogic(): void;
    /**
     * Sets listeners on the groups elements
     */
    setListeners(): void;
    /**
     * Adds a criteria to the group
     *
     * @param crit Instance of Criteria to be added to the group
     */
    addCriteria(crit?: Criteria, redraw?: boolean): void;
    /**
     * Checks the group to see if it has any filled criteria
     */
    checkFilled(): boolean;
    /**
     * Gets the count for the number of criteria in this group and any sub groups
     */
    count(): number;
    /**
     * Rebuilds a sub group that previously existed
     *
     * @param loadedGroup The details of a group within this group
     */
    private _addPrevGroup;
    /**
     * Rebuilds a criteria of this group that previously existed
     *
     * @param loadedCriteria The details of a criteria within the group
     */
    private _addPrevCriteria;
    /**
     * Checks And the criteria using AND logic
     *
     * @param rowData The row data to be checked against the search criteria
     * @returns boolean The result of the AND search
     */
    private _andSearch;
    /**
     * Checks And the criteria using OR logic
     *
     * @param rowData The row data to be checked against the search criteria
     * @returns boolean The result of the OR search
     */
    private _orSearch;
    /**
     * Removes a criteria from the group
     *
     * @param criteria The criteria instance to be removed
     */
    private _removeCriteria;
    /**
     * Sets the listeners in group for a criteria
     *
     * @param criteria The criteria for the listeners to be set on
     */
    private _setCriteriaListeners;
    /**
     * Set's the listeners for the group clear button
     */
    private _setClearListener;
    /**
     * Sets listeners for sub groups of this group
     *
     * @param group The sub group that the listeners are to be set on
     */
    private _setGroupListeners;
    /**
     * Sets up the Group instance, setting listeners and appending elements
     */
    private _setup;
    /**
     * Sets the listener for the logic button
     */
    private _setLogicListener;
    /**
     * Toggles the logic for the group
     */
    private _toggleLogic;
}
