/// <reference types="jquery" />
/// <reference types="datatables.net" />
import * as builderType from './searchBuilder';
export interface IClasses {
    button: string;
    buttonContainer: string;
    condition: string;
    container: string;
    data: string;
    delete: string;
    dropDown: string;
    greyscale: string;
    input: string;
    italic: string;
    joiner: string;
    left: string;
    notItalic: string;
    option: string;
    right: string;
    select: string;
    value: string;
    vertical: string;
}
export interface ICondition {
    conditionName: string | ((dt: any, i18n: any) => string);
    init: (that?: Criteria, fn?: (thatAgain: Criteria, el: JQuery<HTMLElement>) => void, preDefined?: string[]) => JQuery<HTMLElement> | Array<JQuery<HTMLElement>> | void;
    inputValue: (el: JQuery<HTMLElement>) => string[] | void;
    isInputValid: (val: Array<JQuery<HTMLElement>>, that: Criteria) => boolean;
    search: (value: string, comparison: string[], that: Criteria) => boolean;
}
export interface IOrthogonal {
    display: string;
    search: string;
}
export interface IDom {
    buttons: JQuery<HTMLElement>;
    condition: JQuery<HTMLElement>;
    conditionTitle: JQuery<HTMLElement>;
    container: JQuery<HTMLElement>;
    data: JQuery<HTMLElement>;
    dataTitle: JQuery<HTMLElement>;
    defaultValue: JQuery<HTMLElement>;
    delete: JQuery<HTMLElement>;
    left: JQuery<HTMLElement>;
    right: JQuery<HTMLElement>;
    value: Array<JQuery<HTMLElement>>;
    valueTitle: JQuery<HTMLElement>;
}
export interface IS {
    condition: string;
    conditions: {
        [keys: string]: ICondition;
    };
    data: string;
    dataIdx: number;
    dataPoints: IDataOpt[];
    dateFormat: string | boolean;
    depth: number;
    dt: any;
    filled: boolean;
    index: number;
    origData: string;
    preventRedraw: boolean;
    topGroup: JQuery<HTMLElement>;
    type: string;
    value: string[];
}
export interface IDataOpt {
    index: number;
    origData: string;
    text: string;
}
export interface IDetails {
    condition?: string;
    criteria?: Criteria;
    data?: string;
    index?: number;
    logic?: string;
    origData?: string;
    type?: string;
    value?: string[];
}
/**
 * Sets the value of jQuery for use in the file
 *
 * @param jq the instance of jQuery to be set
 */
export declare function setJQuery(jq: any): void;
/**
 * The Criteria class is used within SearchBuilder to represent a search criteria
 */
export default class Criteria {
    private static version;
    private static classes;
    classes: IClasses;
    dom: IDom;
    c: builderType.IDefaults;
    s: IS;
    constructor(table: any, opts: builderType.IDefaults, topGroup: JQuery<HTMLElement>, index?: number, depth?: number);
    /**
     * Escape html characters within a string
     *
     * @param txt the string to be escaped
     * @returns the escaped string
     */
    private static _escapeHTML;
    /**
     * Default initialisation function for select conditions
     */
    private static initSelect;
    /**
     * Default initialisation function for select array conditions
     *
     * This exists because there needs to be different select functionality for contains/without and equals/not
     */
    private static initSelectArray;
    /**
     * Default initialisation function for input conditions
     */
    private static initInput;
    /**
     * Default initialisation function for conditions requiring 2 inputs
     */
    private static init2Input;
    /**
     * Default initialisation function for date conditions
     */
    private static initDate;
    private static initNoValue;
    private static init2Date;
    /**
     * Default function for select elements to validate condition
     */
    private static isInputValidSelect;
    /**
     * Default function for input and date elements to validate condition
     */
    private static isInputValidInput;
    /**
     * Default function for getting select conditions
     */
    private static inputValueSelect;
    /**
     * Default function for getting input conditions
     */
    private static inputValueInput;
    /**
     * Function that is run on each element as a call back when a search should be triggered
     */
    private static updateListener;
    static dateConditions: {
        [keys: string]: ICondition;
    };
    static momentDateConditions: {
        [keys: string]: ICondition;
    };
    static luxonDateConditions: {
        [keys: string]: ICondition;
    };
    static numConditions: {
        [keys: string]: ICondition;
    };
    static numFmtConditions: {
        [keys: string]: ICondition;
    };
    static stringConditions: {
        [keys: string]: ICondition;
    };
    static arrayConditions: {
        [keys: string]: ICondition;
    };
    private static defaults;
    /**
     * Adds the left button to the criteria
     */
    updateArrows(hasSiblings?: boolean, redraw?: boolean): void;
    /**
     * Destroys the criteria, removing listeners and container from the dom
     */
    destroy(): void;
    /**
     * Passes in the data for the row and compares it against this single criteria
     *
     * @param rowData The data for the row to be compared
     * @returns boolean Whether the criteria has passed
     */
    search(rowData: any[], rowIdx: number): boolean;
    /**
     * Gets the details required to rebuild the criteria
     */
    getDetails(deFormatDates?: boolean): IDetails;
    /**
     * Getter for the node for the container of the criteria
     *
     * @returns JQuery<HTMLElement> the node for the container
     */
    getNode(): JQuery<HTMLElement>;
    /**
     * Populates the criteria data, condition and value(s) as far as has been selected
     */
    populate(): void;
    /**
     * Rebuilds the criteria based upon the details passed in
     *
     * @param loadedCriteria the details required to rebuild the criteria
     */
    rebuild(loadedCriteria: IDetails): void;
    /**
     * Sets the listeners for the criteria
     */
    setListeners(): void;
    /**
     * Adjusts the criteria to make SearchBuilder responsive
     */
    private _adjustCriteria;
    /**
     * Builds the elements of the dom together
     */
    private _buildCriteria;
    /**
     * Clears the condition select element
     */
    private _clearCondition;
    /**
     * Clears the value elements
     */
    private _clearValue;
    /**
     * Gets the options for the column
     *
     * @returns {object} The options for the column
     */
    private _getOptions;
    /**
     * Populates the condition dropdown
     */
    private _populateCondition;
    /**
     * Populates the data select element
     */
    private _populateData;
    /**
     * Populates the Value select element
     *
     * @param loadedCriteria optional, used to reload criteria from predefined filters
     */
    private _populateValue;
    /**
     * Provides throttling capabilities to SearchBuilder without having to use dt's _fnThrottle function
     * This is because that function is not quite suitable for our needs as it runs initially rather than waiting
     *
     * @param args arguments supplied to the throttle function
     * @returns Function that is to be run that implements the throttling
     */
    private _throttle;
}
